<?php

function lstOptions($lst) {
	$result = '';
	foreach($lst as $k => $v) {
		$result .= '<option value="'.$k.'">'.$v.'</option>';
	}
	return $result;
}

function gridOptions($lst) {
	$result = '';
	foreach($lst as $k => $v) {
		$result .= ($result == '' ? '' : ';').$k.':'.$v;
	}
	return $result;
}

function gridOptionsTb($lst, $fldkey, $fldval) {
	$result = '';
	foreach($lst as $l) {
		$result .= ($result == '' ? '' : ';').$l[$fldkey].':'.$l[$fldval];
	}
	return $result;
}

function lstForChart($lst) {
	$res = '';
	if ($lst && count($lst) > 0) {
		$res .= '[';
		foreach($lst as $l) {
			$res .= ($res == '[' ? '' : ',') . '[' . $l['DT'].','.$l['NB'] . ']';
		}
		$res .= ']';
	}
	else
		$res = '[]';
	return $res;
}

include 'ImageResize.php';

function doResize($upload_dir, $name) {
	$image = new \Eventviva\ImageResize($upload_dir . $name);
	$image->resizeToBestFit(300, 300);
	$image->save($upload_dir . 'medium/'. $name);
	
	$image->resizeToBestFit(60, 60);
	$image->save($upload_dir . 'small/'. $name);
}

function uploadFile($upload_dir, $myFile) {

    if ($myFile["error"] !== UPLOAD_ERR_OK) {
        echo "<p>An error occurred.</p>";
        die;
    }

    // ensure a safe filename
    $name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);

    // don't overwrite an existing file
    $i = 0;
    $parts = pathinfo($name);
    while (file_exists($upload_dir . $name)) {
        $i++;
        $name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
    }

    // preserve file from temporary directory
    $success = move_uploaded_file($myFile["tmp_name"],
        $upload_dir . $name);
    if (!$success) { 
        echo "Unable to save file.";
        die;
    }
	else {
		doResize($upload_dir, $name);		
		return $name;
	}
	
    // set proper permissions on the new file
    //chmod(upload_dir . $name, 0644);
}

function NfcUpCase($string) {
    $new_string = str_replace(
        array(
            'à', 'â', 'ä', 'á', 'ã', 'å',
            'î', 'ï', 'ì', 'í',
            'ô', 'ö', 'ò', 'ó', 'õ', 'ø',
            'ù', 'û', 'ü', 'ú',
            'é', 'è', 'ê', 'ë',
            'ç', 'ÿ', 'ñ',
            'À', 'Â', 'Ä', 'Á', 'Ã', 'Å',
            'Î', 'Ï', 'Ì', 'Í',
            'Ô', 'Ö', 'Ò', 'Ó', 'Õ', 'Ø',
            'Ù', 'Û', 'Ü', 'Ú',
            'É', 'È', 'Ê', 'Ë',
            'Ç', 'Ÿ', 'Ñ',
        ),
        array(
            'a', 'a', 'a', 'a', 'a', 'a',
            'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u',
            'e', 'e', 'e', 'e',
            'c', 'y', 'n',
            'A', 'A', 'A', 'A', 'A', 'A',
            'I', 'I', 'I', 'I',
            'O', 'O', 'O', 'O', 'O', 'O',
            'U', 'U', 'U', 'U',
            'E', 'E', 'E', 'E',
            'C', 'Y', 'N',
        ),
        $string);
     
    return str_replace(' ', '', strtoupper($new_string));
}



?>