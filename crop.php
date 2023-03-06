<?php


// Check if PNG is transparent, from https://christianwood.net/posts/png-files-are-complicate/
function isPngTransparent(string $file): bool {
	// PNG Types
	$PNG_GRAYSCALE = 0;
	$PNG_RGB = 2;
	$PNG_PALETTE = 3;
	$PNG_GRAYSCALE_ALPHA = 4;
	$PNG_RGBA = 6;
	
	// Bit offsets
	$ColorTypeOffset = 25;
	
  if ($colorTypeByte = file_get_contents($file, false, null, $ColorTypeOffset, 1)) {
	$type = ord($colorTypeByte);
	$image = imagecreatefrompng($file);

	// Palette-based PNGs may have one or more values that correspond to the color to use as transparent
	// PHP returns the first fully transparent color for palette-based images
	$transparentColor = imagecolortransparent($image);

	// Grayscale, RGB, and Palette-based images must define a color that will be used for transparency
	// if none is set, we can bail early because we know it is a fully opaque image
	if ($transparentColor === -1 && in_array($type, [$PNG_GRAYSCALE, $PNG_RGB, $PNG_PALETTE])) {
	  return false;
	}

	$xs = imagesx($image);
	$ys = imagesy($image);

	for ($x = 0; $x < $xs; $x++) {
	  for ($y = 0; $y < $ys; $y++) {
		$color = imagecolorat($image, $x, $y);

		if ($transparentColor === -1) {
		  $shift = $type === $PNG_RGBA ? 3 : 1;
		  $transparency = ($color >> ($shift * 8)) & 0x7F;

		  if (
			($type === $PNG_RGBA && $transparency !== 0) ||
			($type === $PNG_GRAYSCALE_ALPHA && $transparency === 0)
		  ) {
			return true;
		  }
		} else if ($color === $transparentColor) {
		  return true;
		}
	  }
	}
  }

  return false;
}

// Loop through 15 test logos
for($x = 1; $x <= 15; $x++){
	
	// Source image and ouput filenames
	$img_src = 'logo' . $x . '.png';
	$output_src = 'output' . $x . '.png';
	
	// Create output logo png file
	$output_logo = fopen($output_src, 'w') or die("Unable to open file!");
	fclose($output_logo);


	// Execute James' imagick command
	shell_exec('convert -bordercolor transparent -border 2x2 -trim -gravity center -bordercolor white -border 2x2 -trim -gravity center -background transparent -resize 550x360 -extent 550x360 -bordercolor transparent -border 25x20 ' . $img_src . ' output' . $x . '.png');

	// Check for transparency
	$is_transparent = isPngTransparent($img_src);
	
	// Average image color
	$image = imagecreatefrompng($img_src);
	$width = imagesx($image);
	$height = imagesy($image);
	$pixel = imagescale($image, 1, 1);
	imagecopyresampled($pixel, $image, 0, 0, 0, 0, 1, 1, $width, $height);
	$rgb = imagecolorat($pixel, 0, 0);
	$r = ($rgb >> 16) & 0xFF;
	$g = ($rgb >> 8) & 0xFF;
	$b = $rgb & 0xFF;
	$avg_color = imagecolorsforindex($pixel, $rgb); //you are getting the most common colors in the image
	$hex_color = sprintf('#%02x%02x%02x', $r, $g, $b);


	// Average color brightness
	$color_brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
	
	// Output to browser
	echo '<h3 style="margin-bottom: 0;">Input Logo</h3>';
	echo '<img style="width: 300px; height: auto; border: 1px solid rgba(0,0,0,0.2); background: #e3e3e3;" src="' . $img_src . '"><br>';

	echo '<h3 style="margin-bottom: 0;">Output Logo (after imagick)</h3>';
	echo '<img style="border: 1px solid rgba(0,0,0,0.2); background: #e3e3e3;" src="' . $output_src . '"><br>';
	
	// Output analysis data
	echo '<h3 style="margin-bottom: 0;">Logo Analysis</h3>';
	echo "Average color of the logo: " . '<br>';
	echo '<div style="width: 200px; height: 200px; border: 1px solid black; background: ' . $hex_color . ';"></div>';
	echo '<pre>';
		echo 'RGB:';
		print_r($avg_color);
		echo 'Hex: ' . $hex_color . '<br>';
	echo '</pre>';

	echo "Average color brightness: " . $color_brightness . '<br>';

	echo "Is the background transparent?: " . ($is_transparent ? 'Yes': 'No') . '<br>';

	if($is_transparent && $color_brightness > 130){
		$bg_color = 'Black Background';
	}elseif($is_transparent && $color_brightness <= 130){
		$bg_color = 'White Background';
	}else{
		$bg_color = 'White Background';
	}
	echo "What color should the background be: " . $bg_color;
	
	echo '<hr>';
}
?>
