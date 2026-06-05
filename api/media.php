<?php
include_once(dirname(__FILE__) . '/../include/bootstrap.php');

$photo_id = isset($_GET['photoId']) ? (int) $_GET['photoId'] : 0;
$variant = strtolower((string) ($_GET['variant'] ?? 'asset'));
if (!in_array($variant, array('thumb', 'preview', 'asset', 'full'), true)) {
  $variant = 'asset';
}

canva_connector_cors();

if (!canva_connector_has_valid_media_signature($photo_id, $variant)) {
  canva_connector_require_token(false);
}

if ($photo_id <= 0) {
  canva_connector_json(array('error' => 'photoId is required'), 400);
}

$query = '
SELECT id, file, path
FROM ' . IMAGES_TABLE . '
WHERE id = ' . $photo_id . '
LIMIT 1
';
$row = pwg_db_fetch_assoc(pwg_query($query));
if (!$row) {
  canva_connector_json(array('error' => 'Photo not found'), 404);
}

$path = PHPWG_ROOT_PATH . ltrim((string) $row['path'], './');
if (!is_file($path)) {
  canva_connector_json(array('error' => 'Photo file not found'), 404);
}

$config = canva_connector_read_config();
$info = @getimagesize($path);
$source_mime_type = is_array($info) && !empty($info['mime'])
  ? (string) $info['mime']
  : (preg_match('/\.png$/i', (string) $row['file']) ? 'image/png' : 'image/jpeg');

function canva_connector_variant_options(string $variant, array $config): array
{
  if ($variant === 'thumb') {
    return array(
      'max' => (int) $config['thumb_max_dimension'],
      'quality' => (int) $config['thumb_quality'],
      'cache' => true,
    );
  }

  if ($variant === 'preview') {
    return array(
      'max' => (int) $config['preview_max_dimension'],
      'quality' => (int) $config['preview_quality'],
      'cache' => true,
    );
  }

  if ($variant === 'asset') {
    return array(
      'max' => (int) $config['asset_max_dimension'],
      'quality' => (int) $config['asset_quality'],
      'cache' => true,
    );
  }

  return array('max' => 0, 'quality' => 90, 'cache' => false);
}

function canva_connector_png_has_alpha($image): bool
{
  if (!$image) {
    return false;
  }

  $width = imagesx($image);
  $height = imagesy($image);
  $step_x = max(1, (int) floor($width / 80));
  $step_y = max(1, (int) floor($height / 80));

  for ($y = 0; $y < $height; $y += $step_y) {
    for ($x = 0; $x < $width; $x += $step_x) {
      $rgba = imagecolorat($image, $x, $y);
      if ((($rgba >> 24) & 0x7F) > 0) {
        return true;
      }
    }
  }

  return false;
}

function canva_connector_exif_orientation(string $path, string $mime_type): int
{
  if ($mime_type !== 'image/jpeg' || !function_exists('exif_read_data')) {
    return 1;
  }

  $exif = @exif_read_data($path);
  if (!is_array($exif)) {
    return 1;
  }

  return (int) ($exif['Orientation'] ?? 1);
}

function canva_connector_apply_orientation($image, int $orientation, string $mime_type)
{
  if (!$image || $mime_type !== 'image/jpeg') {
    return $image;
  }

  if (!function_exists('imagerotate')) {
    return $image;
  }

  $background = imagecolorallocate($image, 255, 255, 255);
  $rotated = null;

  switch ($orientation) {
    case 2:
      if (function_exists('imageflip')) {
        imageflip($image, IMG_FLIP_HORIZONTAL);
      }
      return $image;
    case 3:
      $rotated = imagerotate($image, 180, $background);
      break;
    case 4:
      if (function_exists('imageflip')) {
        imageflip($image, IMG_FLIP_VERTICAL);
      }
      return $image;
    case 5:
      if (function_exists('imageflip')) {
        imageflip($image, IMG_FLIP_HORIZONTAL);
      }
      $rotated = imagerotate($image, 270, $background);
      break;
    case 6:
      $rotated = imagerotate($image, 270, $background);
      break;
    case 7:
      if (function_exists('imageflip')) {
        imageflip($image, IMG_FLIP_HORIZONTAL);
      }
      $rotated = imagerotate($image, 90, $background);
      break;
    case 8:
      $rotated = imagerotate($image, 90, $background);
      break;
    default:
      return $image;
  }

  if ($rotated) {
    imagedestroy($image);
    return $rotated;
  }

  return $image;
}

function canva_connector_cache_path(
  int $photo_id,
  string $variant,
  string $path,
  string $extension,
  int $orientation,
  array $options,
  array $config
): string {
  $hash = substr(hash('sha256', implode('|', array(
    $photo_id,
    $variant,
    (string) @filemtime($path),
    (string) @filesize($path),
    'orientation-v1',
    (string) $orientation,
    (string) $options['max'],
    (string) $options['quality'],
    (string) $config['convert_png_to_jpeg'],
  ))), 0, 20);

  return canva_connector_cache_dir() . '/' . $photo_id . '-' . $variant . '-' . $hash . '.' . $extension;
}

function canva_connector_send_file(string $path, string $mime_type, bool $signed): void
{
  canva_connector_clean_output_buffer();

  $mtime = @filemtime($path) ?: time();
  $etag = '"' . sha1($path . '|' . $mtime . '|' . (@filesize($path) ?: 0)) . '"';
  if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
  }

  header('Content-Type: ' . $mime_type);
  header('Content-Length: ' . (string) filesize($path));
  header('Cache-Control: ' . ($signed ? 'public' : 'private') . ', max-age=3600');
  header('ETag: ' . $etag);
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
  readfile($path);
  exit;
}

function canva_connector_build_variant(
  string $source_path,
  string $source_mime_type,
  string $variant,
  array $options,
  array $config
) {
  if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
    return null;
  }

  $size = @getimagesize($source_path);
  if (!is_array($size)) {
    return null;
  }

  $source = null;
  if ($source_mime_type === 'image/png' && function_exists('imagecreatefrompng')) {
    $source = @imagecreatefrompng($source_path);
  } elseif ($source_mime_type === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
    $source = @imagecreatefromjpeg($source_path);
  }

  if (!$source) {
    return null;
  }

  $orientation = canva_connector_exif_orientation($source_path, $source_mime_type);
  $source = canva_connector_apply_orientation($source, $orientation, $source_mime_type);
  $source_width = imagesx($source);
  $source_height = imagesy($source);
  if ($source_width <= 0 || $source_height <= 0) {
    imagedestroy($source);
    return null;
  }

  $max = (int) $options['max'];
  $scale = $max > 0 ? min(1, $max / max($source_width, $source_height)) : 1;
  $target_width = max(1, (int) round($source_width * $scale));
  $target_height = max(1, (int) round($source_height * $scale));

  $convert_png = (string) $config['convert_png_to_jpeg'];
  $has_alpha = $source_mime_type === 'image/png' && canva_connector_png_has_alpha($source);
  $output_mime_type = $source_mime_type;
  if ($source_mime_type === 'image/png') {
    if (
      $convert_png === 'always'
      || ($variant !== 'asset' && $convert_png === 'auto' && !$has_alpha)
    ) {
      $output_mime_type = 'image/jpeg';
    }
  }

  $extension = $output_mime_type === 'image/png' ? 'png' : 'jpg';
  $cache_path = canva_connector_cache_path(
    (int) ($_GET['photoId'] ?? 0),
    $variant,
    $source_path,
    $extension,
    $orientation,
    $options,
    $config
  );
  if (is_file($cache_path)) {
    imagedestroy($source);
    return array('path' => $cache_path, 'mimeType' => $output_mime_type);
  }

  $target = imagecreatetruecolor($target_width, $target_height);
  if (!$target) {
    imagedestroy($source);
    return null;
  }

  if ($output_mime_type === 'image/png') {
    imagealphablending($target, false);
    imagesavealpha($target, true);
    $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
    imagefilledrectangle($target, 0, 0, $target_width, $target_height, $transparent);
  } else {
    $white = imagecolorallocate($target, 255, 255, 255);
    imagefilledrectangle($target, 0, 0, $target_width, $target_height, $white);
  }

  imagecopyresampled(
    $target,
    $source,
    0,
    0,
    0,
    0,
    $target_width,
    $target_height,
    $source_width,
    $source_height
  );

  $ok = false;
  if ($output_mime_type === 'image/png') {
    $ok = imagepng($target, $cache_path, 6);
  } else {
    $ok = imagejpeg($target, $cache_path, (int) $options['quality']);
  }

  imagedestroy($target);
  imagedestroy($source);

  if (!$ok || !is_file($cache_path)) {
    @unlink($cache_path);
    return null;
  }

  return array('path' => $cache_path, 'mimeType' => $output_mime_type);
}

$signed = canva_connector_has_valid_media_signature($photo_id, $variant);
$options = canva_connector_variant_options($variant, $config);

if ($variant === 'full' || empty($options['cache'])) {
  canva_connector_send_file($path, $source_mime_type, $signed);
}

$variant_file = canva_connector_build_variant($path, $source_mime_type, $variant, $options, $config);
if (is_array($variant_file)) {
  canva_connector_send_file($variant_file['path'], $variant_file['mimeType'], $signed);
}

canva_connector_send_file($path, $source_mime_type, $signed);
