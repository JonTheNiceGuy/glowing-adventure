<?php
// ********************************************************
// *** Show sources at birth, baptise, marriage, etc.   ***
// ********************************************************
//error_reporting(E_ALL);

/* *** Source function ***
	function show_sources2
	$connect_kind = person/ family/ address
	$connect_sub_kind = birth/ baptise/ etc.
	$connect_connect_id = id (gedcomnumber or direct table id)
*/
function show_sources2($connect_kind,$connect_sub_kind,$connect_connect_id){
	global $dbh, $db_functions, $tree_id, $user, $humo_option, $language, $family_id, $url_path, $uri_path;
	global $main_person, $descendant_report, $pdf_source;
	global $source_footnotes, $screen_mode, $pdf_footnotes, $pdf;
	global $source_footnote_connect_id;
	global $source_combiner;
	$text=false;

	$source_presentation='title';
	if (isset($_SESSION['save_source_presentation'])){
		$source_presentation=$_SESSION['save_source_presentation'];
	}

	// *** Hide sources in mobile version ***
	if ($screen_mode=='mobile') $source_presentation='hide';

	if ($user['group_sources']!='n' AND $source_presentation!='hide' AND $screen_mode!='STAR'){

		// *** Search for all connected sources ***
		$connect_qry="SELECT * FROM humo_connections WHERE connect_tree_id='".$tree_id."'
			AND connect_kind='".$connect_kind."'
			AND connect_sub_kind='".$connect_sub_kind."'
			AND connect_connect_id='".$connect_connect_id."'
			ORDER BY connect_order";
		$connect_sql=$dbh->query($connect_qry);
		while($connectDb=$connect_sql->fetch(PDO::FETCH_OBJ)){
			// *** Get shared source, and check for restriction (in source and user group) ***
			$source_status='publish';
			if ($connectDb->connect_source_id){
				$sourceDb = $db_functions->get_source($connectDb->connect_source_id);
				if ($user['group_show_restricted_source']=='n' AND $sourceDb->source_status=='restricted'){
					$source_status='restricted';
				}
			}

			// *** PDF export, for now only SHARED sources are exported! ***
			if ($screen_mode=='PDF' AND $connectDb->connect_source_id AND $source_status=='publish'){

				// *** Show sources as footnotes ***
				if (!isset($source_footnotes)){
					$source_footnotes[]=$sourceDb->source_id;
					$pdf_footnotes[] = $pdf->AddLink();
					$pdf_source[safe_text_db($connectDb->connect_source_id)]=safe_text_db($connectDb->connect_source_id);
				}

				$source_check=false;
				for ($j=0; $j<=(count($source_footnotes)-1); $j++){
					if ($sourceDb->source_id==$source_footnotes[$j]){
						if($source_presentation=='footnote') {
							$text.=' ('.__('sources').' '.($j+1).')';
							$text.='~'; // delimiter
						}
						else {
							$text.=', '.__('source').': ';
							if ($sourceDb->source_title){ $text.= " ".trim($sourceDb->source_title); }
							if ($sourceDb->source_date or $sourceDb->source_place) {
								$text.=" ".date_place($sourceDb->source_date, $sourceDb->source_place); }
							// append num of source in list for use with fpdf_extend.php to add the right link:
							$text .= "!!".($j+1).'~'; // ~ is delimiter for multiple sources
						}
						$source_check=true;
					}
				}
				if ($source_check!=true){
					if($source_presentation=='footnote') {
						$text.=' ('.__('sources').' '.($j+1).')';
						$text.='~'; // delimiter
					}
					else {
						$text.=', '.__('source').': ';
						if ($sourceDb->source_title){ $text.= " ".trim($sourceDb->source_title); }
						if ($sourceDb->source_date or $sourceDb->source_place) { $text.=" ".date_place($sourceDb->source_date, $sourceDb->source_place); }
						// append num of source in list as !!3 for use with fpdf_extend.php to add the right link:
						$text .= "!!".($j+1).'~'; // ~ is delimiter for multiple sources
					}
					$pdf_source[safe_text_db($connectDb->connect_source_id)]=safe_text_db($connectDb->connect_source_id);
					$pdf_footnotes[] = $pdf->AddLink();
					$source_footnotes[]=$sourceDb->source_id;
				}
			}
			elseif ($source_presentation=='footnote' AND $source_status=='publish'){
				// *** Combine footnotes with the same source including the same source role and source page... ***
				$combiner_check=$connectDb->connect_source_id.'_'.$connectDb->connect_role.'_'.$connectDb->connect_page.'_'.$connectDb->connect_date.' '.$connectDb->connect_place.' '.$connectDb->connect_text;
				$check=false;
				// *** Check if the source (including role and page) is allready used ***
				if ($source_combiner){
					for ($j=0; $j<=(count($source_combiner)-1); $j++){
						if ($source_combiner[$j]==$combiner_check){ $check=true; $j2=$j+1;}
					}
				}
				// *** Source not found in array, add new source to array ***
				if (!$check){
					// *** Save new combined source-role-page for check ***
					$source_combiner[]=$combiner_check;
					// *** Save connect_id to show footnotes ***
					$source_footnote_connect_id[]=$connectDb->connect_id;
					// *** Number to show for footnote ***
					$j2=count($source_footnote_connect_id);
				}
				// *** Test line for footnotes ***
				//$text.=' '.$combiner_check.' ';

// NOT YET IN PDF EXPORT.
				// *** Add extra text by "Source for person". Otherwise it isn't clear wich source it is ***
				$rtf_text='';
				if ($connect_sub_kind=='person_source') $text.= '. <b>'.__('Source for person').'</b> ';
				elseif ($connect_sub_kind=='family_source') $text.= '. <b>'.__('Source for family').'</b> ';
				elseif ($screen_mode=='RTF') $rtf_text=__('sources').' ';
				$text.=' <a href="'.str_replace("&","&amp;",$_SERVER['REQUEST_URI']).'#source_ref'.$j2.'"><sup>'.$rtf_text.$j2.')</sup></a>';
			}
			else{
				// *** Link to shared source ***
				if ($connectDb->connect_source_id AND $source_status=='publish'){
					// *** Always show title of source, show link only after permission check ***
					if ($user['group_sources']=='j' AND $connect_sub_kind!='person_source' AND $connect_sub_kind!='family_source'){
						$text.= ', <a href="'.$uri_path.'source.php?database='.$_SESSION['tree_prefix'].
						'&amp;id='.$sourceDb->source_gedcomnr.'">'.__('source');
					}
					elseif ($connect_sub_kind=='person_source'){
						$text.= '. <b>'.__('Source for person').'</b>';
						$text.= '<a href="'.$uri_path.'source.php?database='.$_SESSION['tree_prefix'].'&amp;id='.$sourceDb->source_gedcomnr.'">';
					}
					elseif ($connect_sub_kind=='family_source'){
						$text.= '. <b>'.__('Source for family').'</b>';
						$text.= '<a href="'.$uri_path.'source.php?database='.$_SESSION['tree_prefix'].'&amp;id='.$sourceDb->source_gedcomnr.'">';
					}
					else{
						$text.= ', '.__('Source');
					}

					// *** Quality (function show_quality can be found in script: family.php) ***
					if ($connectDb->connect_quality=='0' or $connectDb->connect_quality){
						$quality_text=show_quality($connectDb->connect_quality);
						$text.= ' <i>('.$quality_text.')</i>';
					}

					$text.= ': ';
					if ($sourceDb->source_title){ $text.= ' '.trim($sourceDb->source_title); }
					if ($sourceDb->source_date or $sourceDb->source_place){ $text.=" ".date_place($sourceDb->source_date, $sourceDb->source_place); }
					if ($user['group_sources']=='j') $text.= '</a>'; // *** End of link ***
				} // *** End of shared source ***

				//else{
				//	// *** No shared, show source text ***
				//	if ($connectDb->connect_text){
				//		$text.=', '.__('source').': '.nl2br($connectDb->connect_text);
				//	}
				//}

				// *** Show (extra) source text ***
				if ($connectDb->connect_text AND $source_status=='publish'){
					$text.=', '.__('source text').': '.nl2br($connectDb->connect_text);
				}

				// *** Show picture by source ***
				$result = show_media('connect',$connectDb->connect_id); // *** This function can be found in file: show_picture.php! ***
				$text.= $result[0];

			}


		} // *** Loop multiple source ***

	} // *** End of show sources ***
	return $text;
}


// **************************************************
// *** Show source list if footnotes are selected ***
// **************************************************
function show_sources_footnotes(){
	global $dbh, $db_functions, $tree_id, $source_footnotes, $language, $user;
	global $uri_path, $source_footnote_connect_id;
	$text='';

	if ($source_footnote_connect_id && count($source_footnote_connect_id)>0){
		$text.='<h3>'.__('Sources')."</h3>\n";

		for ($j=0; $j<=(count($source_footnote_connect_id)-1); $j++){
			$connect_qry="SELECT * FROM humo_connections
				WHERE connect_id='".$source_footnote_connect_id[$j]."'";
			$connect_sql=$dbh->query($connect_qry);
			$connectDb=$connect_sql->fetch(PDO::FETCH_OBJ);
			// *** Show shared source data ***
			if ($connectDb->connect_source_id){
				$sourceDb = $db_functions->get_source($connectDb->connect_source_id);
				// *** Always show title of source, show link only after permission check ***
				$text.='<a name="source_ref'.($j+1).'"><b>'.($j+1).')</b></a>';
				if ($user['group_sources']=='j'){
					$text.=' <a href="'.$uri_path.'source.php?database='.$_SESSION['tree_prefix'].
					'&amp;id='.$sourceDb->source_gedcomnr.'">'.__('source').': ';
					if ($sourceDb->source_title){ $text.=" ".trim($sourceDb->source_title); }
					$text.='</a>';
				}
				else{
					if ($sourceDb->source_title){ $text.=' '.trim($sourceDb->source_title); }
				}
				if ($connectDb->connect_date or $connectDb->connect_place){
					//if ($connectDb->source_title){ $text.=', '; }
					$text.=" ".date_place($connectDb->connect_date, $connectDb->connect_place);
				}

				// *** Show extra source text ***
				if ($connectDb->connect_text){
					$text.=' '.nl2br($connectDb->connect_text);
				}
			}

			else{
				// *** No shared source connected ***
				$text.='<a name="source_ref'.($j+1).'">'.($j+1).')</a>';

				// *** Source text ***
				$text.=' '.nl2br($connectDb->connect_text);
			}

			// *** Show rest of source items ***

			// *** Source role ***
			if ($connectDb->connect_role){
				$text.=', '.__('role').': '.$connectDb->connect_role;
			}

			// *** Source page ***
			if ($connectDb->connect_page){
				$text.=', '.__('page').': '.$connectDb->connect_page;
			}

			// *** Show picture by source ***
			$result = show_media('connect',$connectDb->connect_id); // *** This function can be found in file: show_picture.php! ***
			$text.= $result[0];

			$text.="<br>\n";

		} // *** End of loop source footnotes ***
	}
	return $text;
}
?>