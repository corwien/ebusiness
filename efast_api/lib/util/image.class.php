<?php

class GDColor
{
	public $r;
	public $g;
	public $b;

	static public function get_gd_color($img, $color)
	{
		if (4 <= func_num_args()) {
			$args = func_get_args();
			$c = new GDColor($args[1], $args[2], $args[3]);
		}
		else {
			$c = new GDColor($color);
		}

		return $c->get_color($img);
	}

	public function __construct()
	{
		$args = func_get_args();
		$argc = func_num_args();

		if ($argc === 1) {
			if (is_string($args[0]) && (strlen($args[0]) === 7) && ($args[0][0] === "#")) {
				$this->r = intval(substr($args[0], 1, 2), 16);
				$this->g = intval(substr($args[0], 3, 2), 16);
				$this->b = intval(substr($args[0], 5, 2), 16);
			}
			else {
				if (is_array($args[0]) && (3 <= count($args[0]))) {
					$this->r = $args[0][0];
					$this->g = $args[0][1];
					$this->b = $args[0][2];
				}
				else {
					if (is_string($args[0])) {
						$args[0] = self::get_color_by_name($args[0]);
					}

					$args[0] = intval($args[0]);
					$this->r = ($args[0] & 16711680) >> 16;
					$this->g = ($args[0] & 65280) >> 8;
					$this->b = $args[0] & 255;
				}
			}
		}
		else if ($argc === 3) {
			$this->r = intval($args[0]);
			$this->g = intval($args[1]);
			$this->b = intval($args[2]);
		}
		else {
			$this->r = $this->g = $this->b = 0;
		}
	}

	public function get_color($img)
	{
		return imagecolorallocate($img, $this->r, $this->g, $this->b);
	}

	private function get_color_by_name($code, $default = "white")
	{
		switch (strtolower($code)) {
		case "black":
			return 0;
		case "maroon":
			return 8388608;
		case "red":
			return 16711680;
		case "orange":
			return 16753920;
		case "yellow":
			return 16776960;
		case "olive":
			return 8421376;
		case "purple":
			return 8388736;
		case "fuchsia":
			return 16711935;
		case "lime":
			return 65280;
		case "green":
			return 32768;
		case "navy":
			return 128;
		case "blue":
			return 255;
		case "aqua":
			return 65535;
		case "teal":
			return 32896;
		case "silver":
			return 12632256;
		case "gray":
			return 8421504;
		case "ltgray":
			return 12632256;
		case "dkgray":
			return 4210752;
		default:
			return 16777215;
		}
	}
}

class GDFont
{
	private $rect_inc = 0;
	private $path;
	private $size;
	private $rect;
	private $u_x;
	private $u_y;
	private $is_default = true;
	private $rotation = 0;
	private $bkcolor;
	private $text = "";

	public function __construct($size, $font_name = NULL)
	{
		$this->is_default = true;

		if ($font_name) {
			$font_name = ROOT_PATH . "res" . DS . "font" . DS . $font_name . ".ttf";

			if (file_exists($font_name)) {
				$this->is_default = false;
			}
		}

		if ($this->is_default) {
			$this->size = max(0, intval($size));
			$this->set_bkcolor(new GDColor("white"));
		}
		else {
			$this->path = $font_name;
			$this->size = max(5, intval($size));
		}

		$this->set_rotation(0);
	}

	public function __get($name)
	{
		if (strcmp("text", $name) == 0) {
			return $this->text;
		}
		else if (strcmp("rotation", $name) == 0) {
			return $this->rotation;
		}
		else if (strcmp("bkcolor", $name) == 0) {
			return $this->bkcolor;
		}
	}

	public function set_text($text)
	{
		$this->text = $text;
		$this->rebuild_box();
		return $this;
	}

	public function set_rotation($rotation)
	{
		$this->rotation = (int) $rotation;
		if (($this->rotation !== 90) && ($this->rotation !== 180) && ($this->rotation !== 270)) {
			$this->rotation = 0;
		}

		$this->rebuild_box();
		return $this;
	}

	public function set_bkcolor($bkcolor)
	{
		$this->bkcolor = $bkcolor;
		return $this;
	}

	public function get_dimension()
	{
		if ($this->is_default) {
			$w = imagefontwidth($this->size) * strlen($this->text);
			$h = imagefontheight($this->size);
			if (($this->rotation === 90) || ($this->rotation === 270)) {
				return array($h, $w);
			}
			else {
				return array($w, $h);
			}
		}
		else {
			$w = 0;
			$h = 0;

			if ($this->rect !== NULL) {
				$minx = min(array($this->rect[0], $this->rect[2], $this->rect[4], $this->rect[6]));
				$maxx = max(array($this->rect[0], $this->rect[2], $this->rect[4], $this->rect[6]));
				$miny = min(array($this->rect[1], $this->rect[3], $this->rect[5], $this->rect[7]));
				$maxy = max(array($this->rect[1], $this->rect[3], $this->rect[5], $this->rect[7]));
				$w = $maxx - $minx;
				$h = $maxy - $miny;
			}

			if (($this->rotation === 90) || ($this->rotation === 270)) {
				return array($h + $this->rect_inc, $w);
			}
			else {
				return array($w + $this->rect_inc, $h);
			}
		}
	}

	public function draw($img, $color, $x, $y)
	{
		if (is_object($color)) {
			$color_id = $color->get_color($img);
		}
		else {
			$color_id = GDColor::get_gd_color($img, $color);
		}

		if ($this->is_default) {
			if ($this->rotation !== 0) {
				$w = imagefontwidth($this->size) * strlen($this->text);
				$h = imagefontheight($this->size);
				$gd = imagecreatetruecolor($w, $h);

				if ($this->bkcolor !== NULL) {
					imagefilledrectangle($gd, 0, 0, $w - 1, $h - 1, GDColor::get_gd_color($gd, $this->bkcolor));
				}

				imagestring($gd, $this->size, 0, 0, $this->text, $color_id);
				$gd = imagerotate($gd, $this->rotation, 0);
				imagecopy($img, $gd, $x, $y, 0, 0, imagesx($gd), imagesy($gd));
			}
			else {
				imagestring($img, $this->size, $x, $y, $this->text, $color_id);
			}
		}
		else {
			$dimension = $this->get_dimension();

			if ($this->rotation === 0) {
				$y += abs(min($this->rect[5], $this->rect[7]));
			}
			else if ($this->rotation === 90) {
				$x += abs(min($this->rect[5], $this->rect[7]));
				$y += $dimension[1];
			}
			else if ($this->rotation === 180) {
				$x += $dimension[0];
				$y += abs(max($this->rect[1], $this->rect[3]));
			}
			else if ($this->rotation === 270) {
				$x += abs(max($this->rect[1], $this->rect[3]));
			}

			imagettftext($img, $this->size, $this->rotation, $x, $y, $color_id, $this->path, $this->text);
		}
	}

	private function rebuild_box()
	{
		if ($this->is_default) {
			return NULL;
		}

		$gd = imagecreate(1, 1);
		$this->rect = imagettftext($gd, $this->size, 0, 0, 0, 0, $this->path, $this->text);
		$this->u_x = abs($this->rect[0]);
		$this->u_y = abs($this->rect[1]);
		if (($this->rotation === 90) || ($this->rotation === 270)) {
			$this->u_x ^= $this->u_y ^= $this->u_x ^= $this->u_y;
		}
	}
}

class GDLabel
{
	const LEFT = 0;
	const TOP = 0;
	const CENTER = 1;
	const RIGHT = 2;
	const BOTTOM = 2;
	const POSITION_CENTER = 4;
	const POSITION_LEFT = 3;
	const POSITION_TOP = 2;
	const POSITION_RIGHT = 1;
	const POSITION_BOTTOM = 0;

	private $font;
	private $position;
	private $alignment;
	private $offset;
	private $space;

	public function __construct($text = "", GDFont $font = NULL, $position = self::POSITION_BOTTOM, $alignment = self::CENTER)
	{
		$font = ($font === NULL ? new GDFont(5) : $font);
		$this->font = clone $font;
		$this->font->set_rotation(0);
		$this->font->set_bkcolor(new GDColor("white"));
		$this->font->set_text($text);
		$this->set_position($position);
		$this->set_alignment($alignment);
		$this->set_space(4);
		$this->set_offset(0);
		$this->set_rotation(0);
		$this->set_bkcolor(new GDColor("white"));
	}

	public function __get($name)
	{
		if (strcmp("text", $name) == 0) {
			return $this->font->text;
		}
		else if (strcmp("position", $name) == 0) {
			return $this->position;
		}
		else if (strcmp("alignment", $name) == 0) {
			return $this->alignment;
		}
		else if (strcmp("offset", $name) == 0) {
			return $this->offset;
		}
		else if (strcmp("space", $name) == 0) {
			return $this->space;
		}
		else if (strcmp("rotation", $name) == 0) {
			return $this->font->rotation;
		}
		else if (strcmp("bkcolor", $name) == 0) {
			return $this->font->bkcolor;
		}
	}

	public function set_text($text)
	{
		$this->font->set_text($text);
		return $this;
	}

	public function set_font($font)
	{
		if ($font) {
			$this->font->set_bkcolor($font->bkcolor);
			$this->font->set_rotation($font->rotation);
		}

		return $this;
	}

	public function set_rotation($rotation)
	{
		$this->font->set_rotation($rotation);
		return $this;
	}

	public function set_bkcolor($bkcolor)
	{
		$this->font->set_bkcolor($bkcolor);
		return $this;
	}

	public function set_position($position)
	{
		$position = intval($position);
		if (($position < self::POSITION_TOP) || (self::POSITION_CENTER < $position)) {
			$position = self::POSITION_BOTTOM;
		}

		$this->position = $position;
		return $this;
	}

	public function set_alignment($alignment)
	{
		$alignment = intval($alignment);
		if (($alignment < self::LEFT) || (self::RIGHT < $alignment)) {
			$alignment = self::CENTER;
		}

		$this->alignment = $alignment;
		return $this;
	}

	public function set_offset($offset)
	{
		$this->offset = intval($offset);
		return $this;
	}

	public function set_space($space)
	{
		$this->space = max(0, intval($space));
		return $this;
	}

	public function get_dimension()
	{
		$w = 0;
		$h = 0;
		$dimension = $this->font->get_dimension();
		$w = $dimension[0];
		$h = $dimension[1];
		if (($this->position === self::POSITION_TOP) || ($this->position === self::POSITION_BOTTOM)) {
			$h += $this->space;
			$w += max(0, $this->offset);
		}
		else {
			$w += $this->space;
			$h += max(0, $this->offset);
		}

		return array($w, $h);
	}

	public function draw($img, $x1, $y1, $x2, $y2, $color = 0)
	{
		$x = 0;
		$y = 0;
		$dim = $this->font->get_dimension();

		if ($this->position === self::POSITION_CENTER) {
			$x = ((($x2 - $x1) / 2) + $x1) - ($dim[0] / 2);
			$y = ((($y2 - $y1) / 2) + $y1) - ($dim[1] / 2);
		}
		else {
			if (($this->position === self::POSITION_TOP) || ($this->position === self::POSITION_BOTTOM)) {
				if ($this->position === self::POSITION_BOTTOM) {
					$y = $y2 - $this->space - $dim[1];
				}
				else if ($this->position === self::POSITION_TOP) {
					$y = $y1 + $this->space;
				}

				if ($this->alignment === self::CENTER) {
					$x = ((($x2 - $x1) / 2) + $x1) - ($dim[0] / 2);
				}
				else if ($this->alignment === self::LEFT) {
					$x = $x1 + $this->offset;
				}
				else {
					$x = $x2 - $this->offset - $dim[0];
				}
			}
			else {
				if ($this->position === self::POSITION_RIGHT) {
					$x = $x2 - $this->space - $dim[0];
				}
				else if ($this->position === self::POSITION_LEFT) {
					$x = $x1 + $this->space;
				}

				if ($this->alignment === self::CENTER) {
					$y = ((($y2 - $y1) / 2) + $y1) - ($dim[1] / 2);
				}
				else if ($this->alignment === self::TOP) {
					$y = $y1 + $this->offset + ($dim[1] / 2);
				}
				else {
					$y = $y2 - $this->offset - $dim[1];
				}
			}
		}

		$this->font->draw($img, GDColor::get_gd_color($img, $color), $x, $y);
	}
}


?>
