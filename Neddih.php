<?php
/** 
 * Classe Neddih
 *
 */
class Neddih
{    	
	public static function uploadFile($file, $type='insertion') {
		if($type == 'extraction'){
			$type = 'fileExtraction';
		} else {
			$type = 'fileInsertion';
		}

		$image_location = false;
		$error = "";
		if(isset($file) && $file["size"] > 0){
			if($file["type"] == "image/bmp" && ($file["size"] < 20000))	{
				if ($file["error"] > 0) {
					$error .= "Return Code: " . $file["error"] . "<br />";
				} else {
					$image_location = "upload/" . $file["name"];
					if (!file_exists("upload/" . $file["name"])){
						move_uploaded_file($file["tmp_name"],
											"upload/" . $file["name"]);
					}
					$_SESSION[$type] = $image_location;
				}
			} else {
				$error .= "\n";
				$error .= "Invalid file";
			}
		} else if(isset($_SESSION[$type])){
			$image_location = $_SESSION[$type];
		}
		return array('error' => $error, 'image_location' => $image_location );
	}

	public static function insertion($image_location, $message, $password=false) {
		$octet_decoupe = array();
		$f_image = fopen($image_location, 'r+b'); // Open the file
		fseek($f_image, 54); // Move cursor after header setion

		if($password){
			//Mettre en place le système de MDP ?
		}

		for($i=0;$i<strlen($message);$i++)
		{
			$char = $message[$i];
			$byte_value = ord($char); //char number in ASCII table
			$byte_binary = decbin($byte_value);//binary number
			$byte_binary = str_pad($byte_binary, 8, '0', STR_PAD_LEFT); // add leading 0 in case the byte doesn't make 8 long
			$byte_sections = str_split($byte_binary, 2); // we split the byte into section of 2 (example for 01111001 => [01, 11, 10, 01])

			//for each char on the message we make 4 iterations here
			foreach($byte_sections as $byte_section)
			{
				
				$byte_image = fread($f_image, 1); // we get one byte as a char
				$byte_image = ord($byte_image); // char number in ASCII table
				$byte_image = $byte_image & 252; // We make the two last bits equal to zero
				$byte_section = bindec($byte_section); // We convert in base 10
				$byte_image += $byte_section; // We add the two last bits to the image byte

				fseek($f_image, -1, SEEK_CUR); // we move the cursor backward because the following line will make it go forward
				fputs($f_image, chr($byte_image)); //we write the modified char on the image		
			}
		}
		fclose($f_image);
	}

	public static function extraction($image_location, $password=false) {
		$buffer = "";
		$message = "";
		$i = 0; //stock the number of bytes read
		$f_image = fopen($image_location, 'rb');
		fseek($f_image, 54); // Move cursor after header setion

		if($password){
			//Mettre en place le système de MDP ?
		}

		while(!feof($f_image)){
			$byte_image = fread($f_image, 1); // we get one byte as a char
			$i++; //number of bytes read increment
			$byte_image = ord($byte_image); //char number in ASCII table
			$bits_last    = $byte_image%4; // we get the last 2 bits
			$buffer     = ($buffer << 2) | $bits_last; //we shift the buffer to the left for 2 and we make a "OR" between buffer and bits_last

			if($i % 4 == 0) //when we read 4 bytes (so when we recompose a byte)
			{
				//If it's the end of the message then we stop
				if($buffer == 26){ break; }

				$message .= chr($buffer); //We add the new char
				$buffer = "";//reset the buffer
			}
		}
		return $message;
	}
}
?>