DELIMITER $$
DROP PROCEDURE IF EXISTS exp_get_multiple_objects_info$$
CREATE PROCEDURE exp_get_multiple_objects_info (IN prefix VARCHAR(32), IN INTIME VARCHAR(255), IN INYEAR INT, IN INMONTH VARCHAR(48), IN INLANG VARCHAR(64), IN extraconditions VARCHAR(255), IN USEKIDS BOOLEAN)
BEGIN
	/* this is to create an appropriately maximum size for GROUP_CONCAT results. Should be a large number to avoid interruptions */
	SET SESSION group_concat_max_len = 5000;

	/* these columns always get picked, no matter what */
	SET @defaultrows = 'SELECT x.*, z.STATUS, REPLACE(SUBSTRING(x.TAGS, LOCATE("Row ", x.TAGS), 6), ",", "") AS ROW, REPLACE(SUBSTRING(x.TAGS, LOCATE("Item ", x.TAGS), 7), ",", "") AS ITEM';

	/*
	this is the database table prefix for ExpressionsEngine db tables.
	Default shown below. All custom tables used follow this same format as other tables in application.
	One could potentially use a different prefix, but if not, the default defined below will be used instead.
	*/
	IF prefix = "" THEN
		SET prefix = "exp_";
	END IF;

	/*
	This column is optional, based on whether the 'USEKIDS' parameter is set to true.
	If so, then this is formatted as json.  Numerical json array of json objects.
	Schema derived from that  Elan_api_lib.php, 'get_monthly_focus' method, '$children' array of each $monthly_focus member.
	 */
	SET @children = ', (SELECT GROUP_CONCAT("{\\"disabled\\":\\"", IF( LOWER( IFNULL(U.STATUS, "") ) IN ("complete", "passed"), "false", "disabled") ,"\\",
\\"is_link\\":", IF( LOWER(Y.OBJECT_TYPE_NAME) = "link", "true", "false") ,",
\\"is_objective_complete\\":\\"", IFNULL(U.STATUS, "") ,"\\",
\\"is_quiz\\":", IF( LOWER(Y.OBJECT_TYPE_NAME) = "quiz", "true", "false") ,",
\\"is_scorm\\":", IF( LOWER(Y.OBJECT_TYPE_NAME) = "scorm", "true", "false") ,",
\\"is_video\\":", IF( LOWER(Y.OBJECT_TYPE_NAME) = "course", "true", "false") ,",
\\"video_steps\\":\\"", Y.VIDEO_STEPS ,"\\",
\\"link_path\\":\\"", IFNULL(url_decode(Y.LINK_PATH), "\\"\\"") ,"\\",
\\"object_id\\":", Y.OBJECT_ID, ",
\\"parent_object_id\\":", x.OBJECT_ID,",
\\"quiz_percent\\":", Y.PASSING_PERCENT ,",
\\"score\\":\\"", U.SCORE ,"\\",
\\"record_id\\":", IFNULL(U.RECORD_ID, "\\"\\""),",
\\"title\\": \\"", REPLACE(Y.OBJECT_NAME, "\t", "") ,"\\",
\\"type\\":\\"", CONCAT(UCASE(LEFT(Y.OBJECT_TYPE_NAME, 1)),SUBSTRING(Y.OBJECT_TYPE_NAME, 2)) , "\\"}")
	FROM `exp_temp_multiple_objects_info` Y
	LEFT JOIN `exp_temp_user_object_records` U
	ON Y.OBJECT_ID = U.OBJECT_ID
	WHERE FIND_IN_SET(Y.OBJECT_ID, REPLACE(TRIM(x.CHILD_ID_LIST), " ", ""))
) AS CHILDREN';

	/*
	Always used. 'FROM' and 'WHERE/AND' sections.
	*/
	SET @fromwhere = CONCAT(' FROM `', prefix, 'temp_multiple_objects_info` x
		LEFT JOIN `', prefix, 'temp_user_object_records` z ON z.OBJECT_ID = x.OBJECT_ID
WHERE STR_TO_DATE(x.insert_time_gmt, "%M, %d %Y %H:%i:%s") > STR_TO_DATE(\"', INTIME, '\", "%M, %d %Y %H:%i:%s")
		AND x.OBJECT_STATUS="Active"
		AND LOWER(x.TAGS) LIKE CONCAT("%", \"', INMONTH, '\", "%")
AND LOWER(x.TAGS) LIKE CONCAT("%", ', INYEAR, ', "%")
AND LOWER(x.TAGS) LIKE "%row%"
AND LOWER(x.TAGS) LIKE "%item%"
AND LOWER(x.TAGS) LIKE "%monthly focus main%"
AND LOWER(x.TAGS) LIKE CONCAT("%", \"', INLANG, '\", "%")',
extraconditions, ' GROUP BY x.OBJECT_ID ORDER BY REPLACE(SUBSTRING(TAGS, LOCATE("Row ", x.TAGS), 6), ",", ""), REPLACE(SUBSTRING(x.TAGS, LOCATE("Item ", x.TAGS), 7), ",", ""), x.OBJECT_ID ASC');

	/* finalize the query string, find out if this is supposed to have children or not */
	SET @finalQuery = CONCAT( @defaultrows, IF(USEKIDS = 1, @children, ''), @fromwhere);

	PREPARE stmt FROM @finalQuery;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;
END $$