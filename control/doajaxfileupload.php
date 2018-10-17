<?php
	$error = "";
	$msg = "";
    $element = 'upload';
    $save_path = './upload/product';
    $filename = '';

	if(!empty($_FILES[$element]['error'])) {
		switch($_FILES[$element]['error'])
		{
			case '1':
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case '2':
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case '3':
				$error = 'The uploaded file was only partially uploaded';
				break;
			case '4':
				$error = 'No file was uploaded.';
				break;

			case '6':
				$error = 'Missing a temporary folder';
				break;
			case '7':
				$error = 'Failed to write file to disk';
				break;
			case '8':
				$error = 'File upload stopped by extension';
				break;
			case '999':
			default:
				$error = 'No error code avaiable';
		}
	} elseif(empty($_FILES[$element]['tmp_name']) || $_FILES[$element]['tmp_name'] == 'none') {
		$error = 'No file was uploaded..';
    } else {
        $allowed_image_types = array(
            'image/pjpeg'=>"jpg",
            'image/jpeg'=>"jpg",
            'image/jpg'=>"jpg",
            'image/png'=>"png",
            'image/x-png'=>"png",
            'image/gif'=>"gif"
        );

        $allowed_image_ext = array_unique($allowed_image_types); // do not change this
        $image_ext = "";	// initialise variable, do not change this.
        foreach ($allowed_image_ext as $mime_type => $ext) 
        {
            $image_ext .= strtoupper($ext)." ";
        }

        if($image_ext == "")
        {
            $error = 'file type is not support';
        } else {
            $filename = basename($_FILES[$element]['name']);
	        $file_ext = strtolower(substr('../'.$filename, strrpos($filename, '.') + 1));
            do
            {
                $filename = $save_path.'/'.substr(time(), -1, 8).'.'.$file_ext;
            } while(file_exists($filename));

    		$msg .= " File Name: " . $_FILES[$element]['name'] . ", ";
            $msg .= " File Size: " . @filesize($_FILES[$element]['tmp_name']);
            $ok = @move_uploaded_file($_FILES[$element]['tmp_name'], $filename);
            //for security reason, we force to remove all uploaded file
            @unlink($_FILES[$element]);
        }
	}		
	echo "{";
	echo				"error: '" . $error . "',\n";
    echo				"msg: '" . $msg . "',\n";
    echo                "file: '". $filename ."'\n";
	echo "}";
?>
