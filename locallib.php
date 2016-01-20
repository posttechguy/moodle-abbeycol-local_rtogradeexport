<?php
/**
  * Export grades to CSV file
  *
  * Local library definitions
  *
  * @package    local_rtogradeexport
  * @author     Bevan Holman <bevan@pukunui.com>, Pukunui
  * @author     Priya Ramakrishnan <priya@pukunui.com>, Pukunui
  * @author     Shane Elliott <shane@pukunui.com>, Pukunui
  * @copyright  2015 onwards, Pukunui
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

/**
  * Write the CSV output to file
  *
  * @param string $csv  the csv data
  * @return boolean  success?
*/
function local_rtogradeexport_write_csv_to_file($runhow, $data) {
  global $CFG, $DB;

  $config = get_config('local_rtogradeexport');

  if (($runhow == 'auto' and $config->ismanual) or ($runhow == 'manual' and empty($config->ismanual))) {
    return false;
  }

  if (empty($config->csvlocation)) {
      $config->csvlocation = $CFG->dataroot.'/rtogradeexport';
  }
  if (!isset($config->csvprefix)) {
      $config->csvprefix = '';
  }
  if (!isset($config->lastrun)) {
      // First time run we get all data.
      $config->lastrun = 0;
  }
  // Open the file for writing.
  $filename = $config->csvlocation.'/'.$config->csvprefix.date("Ymd").'-'.date("His").'.csv';
  if ($fh = fopen($filename, 'w')) {

      // Write the headers first.
      fwrite($fh, implode(',', local_rtogradeexport_get_csv_headers())."\r\n");

      $rs = local_rtogradeexport_get_data($config->lastrun, $data);

      if ($rs->valid()) {

          $strnotattempted = get_string('notattempted', 'local_rtogradeexport');

          // Cycle through data and add to file.
          foreach ($rs as $r) {
              // Manually manipulate the grade.
              // We could do this via the grade API but that level of complexity is not required here.
              if (!empty($r->finalgrade)) {
                  if (!empty($r->scale)) {
                      $scalearray = explode(',', $r->scale);
                      $result = $scalearray[$r->finalgrade - 1];
                  } else {
                      $result = $r->finalgrade;
                  }
              } else {
                  $result = $strnotattempted;
              }

              // Format the time.
              if (!empty($r->timemodified)) {
                  $resulttime = date('Y-m-d', $r->timemodified);
              } else {
                  $resulttime = '';
              }
/*
              // Write the line to CSV file.
              fwrite($fh, implode(',', array($r->idnumber,
                                             $r->firstname,
                                             $r->lastname,
                                             $r->unitcode,
                                             $r->batch,
                                             $result,
                                             $r->finalpercent,
                                             $resulttime)
                                 )."\r\n");
*/
              // Write the line to CSV file.
              fwrite($fh, implode(',', array($r->idnumber,
                                             $r->unitcode,
                                             $r->batch,
                                             $result,
                                             $resulttime)
                                 )."\r\n");


          }

          // Close the recordset to free up RDBMS memory.
          $rs->close();
      }

      // Close the file.
      fclose($fh);

      // Set the last run time.
      if ($runhow == 'auto') set_config('local_rtogradeexport', 'lastrun', time());

      return true;
  } else {
      return false;
  }
}

/**
 * Return a record set with the grade, group, enrolment data.
 * We use a record set to minimise memory usage as this report may get quite large.
 *
 * @param integer   $from   time stamp
 * @return object   $DB     record set
 */
function local_rtogradeexport_get_data($from, $data = null) {
    global $DB;
// CONCAT_WS('-', u.id, c.id, g.id),
    $sql = "
        SELECT
            u.id as userid, u.username, u.idnumber,
            u.firstname, u.lastname, x.courseid,
            c.shortname as unitcode, g.name as batch,
            y.finalgrade, y.scale, y.timemodified, y.finalpercent
        FROM
        (
            (
                (
                    (
                        {user} u
                        JOIN
                        (
                            (
                                SELECT ue.userid as userid, e.courseid as courseid
                                FROM {user_enrolments} ue
                                JOIN {enrol} e ON ue.enrolid = e.id
                                WHERE ue.timeend IS NOT NULL AND ue.timemodified >= :from1
                            )
                            UNION
                            (
                                SELECT gg.userid as userid, gi.courseid as courseid
                                FROM {grade_grades} gg
                                JOIN {grade_items} gi
                                ON gi.id = gg.itemid
                                WHERE gg.timemodified IS NOT NULL
                                AND gg.timemodified >= :from2
                                AND gi.itemtype = 'course'
                            )
                        ) AS x ON x.userid = u.id
                    )
                    JOIN {course} c ON x.courseid = c.id %%COURSECLAUSE%%
                )
                LEFT JOIN {groups} g ON g.courseid = c.id
            )
            JOIN {groups_members} gm ON gm.groupid = g.id %%GROUPCLAUSE%% AND gm.userid = u.id
        )
        LEFT JOIN
        (
            SELECT
                gi.itemtype, gi.scaleid, round(gg.finalgrade) as finalgrade,
                s.scale, gg.userid, gi.courseid, gg.timemodified, round(gg.finalgrade/gg.rawgrademax*100) as finalpercent
            FROM
            (
                {grade_items} gi
                JOIN {grade_grades} gg ON gg.itemid = gi.id
            )
            LEFT JOIN {scale} s ON gi.scaleid = s.id
            WHERE gi.itemtype = 'course'
        ) as y ON x.userid = y.userid AND x.courseid = y.courseid
        GROUP BY 1,2,3,4,5,6,7,8
    ";

/*
SELECT u.id as userid, u.username, u.idnumber, u.firstname, u.lastname, x.courseid, c.shortname as unitcode, g.name as batch,
y.finalgrade, y.scale, y.timemodified, y.finalpercent FROM ( ( ( (
{user} u JOIN ( (
    SELECT ue.userid as userid, e.courseid as courseid
    FROM {user_enrolments} ue
    JOIN {enrol} e ON ue.enrolid = e.id
    WHERE ue.timeend IS NOT NULL AND ue.timemodified >= :from1 )
  UNION (
    SELECT gg.userid as userid, gi.courseid as courseid
    FROM {grade_grades} gg
    JOIN {grade_items} gi ON gi.id = gg.itemid
    WHERE gg.timemodified IS NOT NULL AND gg.timemodified >= :from2 AND gi.itemtype = 'course' ) ) AS x ON x.userid = u.id ) JOIN {course} c ON x.courseid = c.id )
    LEFT JOIN {groups} g ON g.courseid = c.id ) JOIN {groups_members} gm ON gm.groupid = g.id AND gm.userid = u.id )
    LEFT JOIN ( SELECT gi.itemtype, gi.scaleid, round(gg.finalgrade) as finalgrade, s.scale, gg.userid, gi.courseid,
    gg.timemodified, round(gg.finalgrade/gg.rawgrademax*100) as finalpercent FROM ( {grade_items} gi JOIN {grade_grades} gg ON gg.itemid = gi.id ) LEFT JOIN {scale} s ON gi.scaleid = s.id WHERE gi.itemtype = 'course' ) as y ON x.userid = y.userid AND x.courseid = y.courseid GROUP BY 1,2,3,4,5,6,7,8

*/



    $params = array();

    if ($data)
    {
        // This for the manually run exports of grades

        $params['from1']  = time() - 60*60*24*185; // Gets all records from 185 days ago
   //     echo $params['from1'];
        $params['from2']  = time() - 60*60*24*185; // Gets all records from 185 days ago
        $params['course'] = $data->course;
        $params['group']  = $data->group;
        $sql              = str_replace("%%COURSECLAUSE%%", ($data->course) ? " AND x.courseid = :course " : "", $sql);
        $sql              = str_replace("%%GROUPCLAUSE%%", ($data->group != "All") ? " AND g.name = :group " : "", $sql);
    }
    else
    {
        // Gets the last run time, removes the seconds from today (which is usually run early in the morning),
        // yesterday, and the day before, (so around 48 hours).
        // It will then allow the export to get the records for the last two days

        //                          seconds of today   seconds of yesterday     seconds of day before that
        $runfrom          = $from - ($from % 86400)      - 86400                  - 86400;
   //     $runfrom          = $from - 60*60*24*185;

        $params['from1']  = $runfrom;
        $params['from2']  = $runfrom;
        $sql              = str_replace("%%COURSECLAUSE%%", "", $sql);
        $sql              = str_replace("%%GROUPCLAUSE%%", "", $sql);
    }
    /*
    if ($_SERVER['REMOTE_ADDR'] == '203.59.120.7')
    {
        print_object($params);
         echo "<pre>$sql</pre>";
    }
    */
    return $DB->get_recordset_sql($sql, $params);
}


/**
 * Return the CSV headers
 *
 * @return array
 */
function local_rtogradeexport_get_csv_headers() {
/*
    return array(
        get_string('idnumber',   'local_rtogradeexport'),
        get_string('firstname',  'local_rtogradeexport'),
        get_string('lastname',   'local_rtogradeexport'),
        get_string('unitcode',   'local_rtogradeexport'),
        get_string('batch',      'local_rtogradeexport'),
        get_string('results',    'local_rtogradeexport'),
        get_string('percentageresult', 'local_rtogradeexport'),
        get_string('resultdate', 'local_rtogradeexport'),
        );
*/


    return array(
        get_string('idnumber',   'local_rtogradeexport'),
        get_string('unitcode',   'local_rtogradeexport'),
        get_string('batch',      'local_rtogradeexport'),
        get_string('results',    'local_rtogradeexport'),
        get_string('resultdate', 'local_rtogradeexport'),
        );
}

