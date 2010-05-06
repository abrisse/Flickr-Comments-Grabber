<?php

/******************************************************************/
/* Name: Flickr Comments Grabber
/* Version: v0.2.3
/* Description: Retrieve your image comments from Flickr
/* Author: Aymeric Brisse <aymeric.brisse@gmail.com>
/* License: GNU GPL 2.0
/******************************************************************/

class phpFlickr {

	var $api_key;

	public function __construct( $api_key ) {	
		$this->api_key = $api_key;	
	}
	
	/*
	 * resetComments
	 * 
	 * Remove the comments that have been retrieved from Flickr
	 */
	public function resetComments() {
		global $pixelpost_db_prefix;
		
		if (mysql_query("DELETE FROM " . $pixelpost_db_prefix . "comments WHERE `url` REGEXP '^http://www.flickr.com/photos/[0-9]+@';"))
			return "&nbsp;&nbsp;<b><font color=red>Flickr comments have been successfully removed.</font></b>";
		else
			return "&nbsp;&nbsp;<b><font color=red>An error occured during the removal of the Flickr comments.</font></b>"; 
	}
	
	/*
	 * grabComments
	 * 
	 * Retrieve the comments
	 */
	public function grabComments( $update_type, $f_id=NULL ) {		
		global $pixelpost_db_prefix;
		
		$comment_num = 0;
		$p_num = 0;
		
		set_time_limit(0); 
	
		if($update_type == "unique") {
			$query = mysql_query("SELECT `img_id`, `tag` FROM " . $pixelpost_db_prefix . "tags WHERE `tag`='$f_id' LIMIT 1;") ;
		} elseif($update_type == "all") {
			$query = mysql_query("SELECT `img_id`, `tag` FROM " . $pixelpost_db_prefix . "tags WHERE `tag` REGEXP '^[0-9]+$';") ;
		}
		
		while($row = mysql_fetch_array($query)) {
			$photo_flick_id = $row['tag'];
			$photo_id = $row['img_id'];
			
			/* Retrieve Comments */
			$comments = $this->grabPhotoComments($photo_flick_id, isset($_GET['min_date']) ? $_GET['min_date'] : NULL);
			
			$dates_flickr = array_map( create_function('$comment', 'return strftime ("%Y-%m-%d %H:%M:%S",$comment["datecreate"]);'), (array)$comments['comments']['comment'] );
			$dates_flickr_mysql = "('" . implode("','", $dates_flickr) . "')";
			$dates_pixelpost = array();
			
			/* Checks which comments have already been retrieved */
			$query_c = mysql_query("SELECT `datetime` FROM " . $pixelpost_db_prefix . "comments where `parent_id`='$photo_id' AND `datetime` IN $dates_flickr_mysql;");

			while ($res = mysql_fetch_array($query_c))
				array_push($dates_pixelpost, $res[0]);
			
			/* Comments to be inserted */
			$dates_pixelpost = array_diff($dates_flickr, $dates_pixelpost);
	
			foreach ((array)$comments['comments']['comment'] as $c) {
				$f_commentdate = strftime ("%Y-%m-%d %H:%M:%S",$c['datecreate']);
				
				if (in_array($f_commentdate, $dates_pixelpost)) {				
					$f_url = "http://www.flickr.com/photos/".$c['author'];
					$f_name = $c['authorname'];
					$f_comment = $c['_content'];
	
					/* Insert the new comment */
					if ($this->saveComment($photo_id,$f_url,$f_commentdate,$f_name,$f_comment))	
						$comment_num++;
				}
			}
			
			$p_num++;
		}
		
		return "&nbsp;&nbsp;<b><font color=red>Flickr comments have been successfully retrieved.</font></b>" . " &nbsp;&nbsp;".$p_num." image(s) checked, ".$comment_num." new comment(s) inserted."; 
		
	}
	
	/*
	 * grabPhotoComments
	 * 
	 * Retrieve the comments of a photo
	 */
	protected function grabPhotoComments( $photo_id, $min_timestamp=NULL ) {
	
		$params = array(
			'api_key'	=> $this->api_key,
			'method'	=> 'flickr.photos.comments.getList',
			'photo_id'	=> $photo_id,
			'format'	=> 'php_serial',
		);
		
		if ($min_timestamp)
			$params['min_comment_date'] = $min_timestamp;

		$encoded_params = array();

		foreach ($params as $k => $v)
			$encoded_params[] = urlencode($k).'='.urlencode($v);
			
		/* Call the Flickr API and decode the response */

		$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
		$rsp = file_get_contents($url);
		$rsp_obj = unserialize($rsp);
		
		return $rsp_obj;
	}
	
	/*
	 * saveComment
	 * 
	 * Save the comments into the database
	 */
	protected function saveComment($photo_pid, $f_url, $f_commentdate, $f_name, $f_comment) {
		global $pixelpost_db_prefix;

		/* Clean the name */
		$f_name = clean_comment($f_name);	
		$f_name = nl2br($f_name);
		
		/* Clean the message */
		$f_comment = clean_comment($f_comment);	
		$f_comment = preg_replace("/((\x0D\x0A){3,}|[\x0A]{3,}|[\x0D]{3,})/","\n\n", $f_comment);
		$f_comment = preg_replace("/(\n){2,}$/mis", "\n", $f_comment);
		$f_comment = nl2br($f_comment);

		$query = "INSERT INTO " . $pixelpost_db_prefix . "comments (`parent_id`, `datetime`, `message`, `name`, `url`, `email`, `publish`)
		VALUES ('$photo_pid', '$f_commentdate', '$f_comment', '$f_name', '$f_url', '$email', 'yes')";
		
		return mysql_query($query);
	}
}

?>
