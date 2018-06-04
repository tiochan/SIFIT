<style>
.calendar_top {
	padding: 30px;
}

th.calendar_top_header {
	font-weight: bold;
	font-size: 24px;
	padding: 15px;
	border:1px dashed #afafaf;
}

tr.calendar_day_of_week {
	margin: 15px;
	background-color: #afafaf;
	height: 30px;
}

td.calendar_day_of_week {
	margin: 15px;
	font-weight: bold;
	text-align: center;
	font-size: 14px;
	width: 50px;
	border:1px solid #444444;
}

td.calendar_day_of_week_total {
	margin: 15px;
	background-color: #aaffaa;
	font-weight: bold;
	text-align: center;
	font-size: 14px;
	width: 50px;
	border:1px solid #444444;
}

tr.calendar_day_label {
	margin: 15px;
	background-color: #efefef;
	padding: 15px;
	height: 60px;
}

td.calendar_day_label {
	font-weight: bold;
	text-align: center;
	font-size: 24px;
	border:1px solid #444444;
}

td.calendar_day_label_today {
	font-weight: bold;
	text-align: center;
	font-size: 24px;
	border:3px solid #4444ff;
}

td.calendar_day_label_weekend {
	background-color: #dfdfdf;
	font-weight: bold;
	text-align: center;
	font-size: 24px;
	border:1px solid #444444;
}

td.calendar_day_label_weekend_today {
	background-color: #dfdfdf;
	font-weight: bold;
	text-align: center;
	font-size: 24px;
	border:3px solid #4444ff;
}

.calendar_day_hours {
	text-align: center;
	font-size: 10px;
}

tr.calendar_footer {
	background-color: #efefef;
	padding: 15px;
	height: 60px;
}

td.calendar_footer {
	font-weight: bold;
	text-align: right;
	font-size: 14px;
}

</style>

<?php
/**
 * @author Sebastian Gomez (tiochan@gmail.com)
 * For: Politechnical University of Catalonia (UPC), Spain.
 *
 * @package lib
 * @subpackage marks
 *
 * Datawindow class for Wake On Lan management.
 */


/*
	Table definition
mysql> describe time_marks;
+-----------+--------------+------+-----+---------+----------------+
| Field     | Type         | Null | Key | Default | Extra          |
+-----------+--------------+------+-----+---------+----------------+
| id        | mediumint(9) | NO   | PRI | NULL    | auto_increment |
| user_id   | mediumint(9) | NO   | MUL | NULL    |                |
| mark_date | date         | NO   |     | NULL    |                |
| marks     | varchar(255) | YES  |     | NULL    |                |
| minutes   | int(11)      | NO   |     | 0       |                |
+-----------+--------------+------+-----+---------+----------------+

*/

	include_once INC_DIR . "/forms/form_elements.inc.php";


	class mark_calendar extends form_element {

		protected $dw_marks;

		public function mark_calendar($doc_name, & $dw_marks) {

			parent::form_element($doc_name, $doc_name);
			$this->dw_marks= $dw_marks;
		}

		public function show() {

			parent::show();

			global $global_db, $USER_ID;


			// FIRST: SET DATES VARIABLES
			$days= array("lu","ma","mi","ju","vi","sa","do");

			$first_timestamp= strtotime("first day of this month");
			$last_timestamp= strtotime("last day of this month");

			$today= date("Y-m-d");
			$first_day= date("j",$first_timestamp);
			$first_day_of_week= date("N", $first_timestamp);
			$last_day= date("j",$last_timestamp);
			$month_name= date("F",$last_timestamp);
			$month_number= date("m", $first_timestamp);
			$year= date("Y", $first_timestamp);

			$sql_first_day= $year . "-" . $month_number . "-01";

			// NEXT: Get marks from dates
			$time_marks= array();

			$query= "SELECT mark_date, minutes from time_marks WHERE user_id='$USER_ID' AND ";
			$query.="mark_date >= '$sql_first_day' AND ";
			$query.="mark_date <= FROM_UNIXTIME('" . $last_timestamp . "')";

			$res= $global_db->dbms_query($query);
			if(!$res) die("ERROR");

			while($row= $global_db->dbms_fetch_row($res)) {
				$time_marks[$row[0]]= $row[1];
			}


			// PAINT CALENDAR
			echo "<table class='calendar_top'>";
			echo "<th colspan=8 class='calendar_top_header'>$year, $month_name</th>";

			echo "<tr class='calendar_day_of_week'>";
			foreach($days as $day) {
				echo "<td class='calendar_day_of_week'>$day</td>";
			}
			echo "<td class='calendar_day_of_week_total'>Total</td>";
			echo "</tr>";


			echo "<tr class='calendar_day_label'>";

			$i=1;

			while($i < $first_day_of_week) {
				echo "<td class='calendar_day_label'>&nbsp;</td>";
				$i++;
			}

			$total_month_hours=0;
			$total_week_hours=0;

			for($j=1; $j <= $last_day; $j++, $i++) {

				$day_number= $j < 10 ? "0". $j : "$j";
				$sql_date= $year . "-" . $month_number . "-" . $day_number;

				if($i > 7) {
					echo "<td class='calendar_day_of_week_total'>" . $total_week_hours . "</td>";
					echo "</tr>";
					echo "<tr class='calendar_day_label'>";
					$total_week_hours=0;
					$i=1;
				}

				if($i<=5) {
					$minutes= isset($time_marks[$sql_date]) ? $time_marks[$sql_date] : 0;
					$hours= round( $minutes / 60, 2);

					$total_month_hours+= $hours;
					$total_week_hours+= $hours;

					if($sql_date == $today) {
						echo "<td class='calendar_day_label_today'>$j<br>";
					} else {
						echo "<td class='calendar_day_label'>$j<br>";
					}
					echo "<font class='calendar_day_hours'>$hours h</font>";
					echo "</td>";
				} else {
				if($sql_date == $today) {
						echo "<td class='calendar_day_label_weekend_today'>$j<br>";
					} else {
						echo "<td class='calendar_day_label_weekend'>$j<br>";
					}
					echo "<font class='calendar_day_hours'>&nbsp;</font></td>";
				}
			}

			while($i<=7) {
				if($i<=5) {
					echo "<td class='calendar_day_label'>&nbsp;</td>";
				} else {
					echo "<td class='calendar_day_label_weekend'>&nbsp;</td>";
				}
				$i++;
			}

			echo "<td class='calendar_day_of_week_total'>$total_week_hours</td>";
			echo "</tr>";

			echo "<tr class='calendar_footer'><td class='calendar_footer' colspan=8>Total: $total_month_hours h</td></tr>";


			echo "</table>";
		}
	}
?>
