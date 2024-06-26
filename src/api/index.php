<?php
/** @noinspection SqlResolve */

require '../../vendor/autoload.php';

require '_conf.php';
require 'models/CrdParser.php';
require 'models/File.php';
require 'models/Song.php';
require 'models/SongIndex.php';


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\DriverManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Slim\Factory\AppFactory;

$DB = DriverManager::getConnection($SQL_CREDENTIALS, new \Doctrine\DBAL\Configuration());

define ('EOL', PHP_EOL);

const AVAILABLE_CHORDS = [
	"A","A7","Ab","Absus4-Db","Am","Am6","Am7","As","Asus2","Asus4",
	"B","Bm","Bm-Ab","Bm-Eb","Bm-Gb",
	"C","C-G","C-H","C7","Cism","Cm","Cmaj7",
	"D","D9","D-Fis","D7","Db","Dbsus2","Ddim","Dm","Dm6","Dm7","Dmaj7","Dsus2","Dsus4",
	"E","E7","E7sus4","Eb","Em","Em6","Em7","Es","Es7","Esus4",
	"F","F-bar","F7","Fis","Fis7","Fism","Fism7","Fm",
	"G","G+","G-H","G7","Gism","Gm","Gm7","Gsus4",
	"H","H7","H7sus4","Hm","Hm7","Hsus4"
];


$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->setBasePath($API_BASE_PATH ? $API_BASE_PATH : '/api');
$app->addBodyParsingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/songs', function (Request $request, Response $response, $args) use(&$DB) {
	$si = new SongIndex();
	$response->getBody()->write(json_encode($si->getSongIndex(), JSON_NUMERIC_CHECK));
	return $response;
});

$app->get('/songs/{songId}', function (Request $request, Response $response, $args) {
	$songId = $args['songId'];
	$song = new Song($songId);
	$data = $song->getData();

	function startsWithRaw($haystack, $needle = "raw") {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	foreach($data as $fieldname => $value){
		if (startsWithRaw($fieldname)){
			$data[$fieldname.'Size'] = $data[$fieldname] ? strlen($data[$fieldname]) : 0;
			unset($data[$fieldname]);
		}
	}

	$response->getBody()->write(json_encode($data, JSON_NUMERIC_CHECK));
	return $response;
});

$app->put('/songs/{songId}', function (Request $request, Response $response, $args) {
	$songId = $args['songId'];
	$song = new Song($songId);
	$data = json_decode($request->getBody(), true);
	$song->setData($data)->save();
	return $response;
});

$app->post('/songs', function (Request $request, Response $response, $args) {
	$song = new Song();
	$data = json_decode($request->getBody(), true);
	$song->setTitle(trim($data['title']));
	$song->setInterpret(trim($data['interpret']));
	$song->save();
	$response->getBody()->write($song->getId());
	return $response;
});

$app->get('/songs/{songId}/html', function (Request $request, Response $response, $args) {
	$songId = $args['songId'];
	$response = $response->withHeader('Content-type', 'text/html');
	$song = new Song($songId);
	$response->getBody()->write($song->getHtml());
	return $response;
});

// testing only
$app->get('/songs/{songId}/crd', function (Request $request, Response $response, $args) {
	$songId = $args['songId'];
	$response = $response->withHeader('Content-type', 'text/html');

	$song = new Song($songId);
	$result = CrdParser::run($song->getData()['text']);
	var_dump($result);
	return $response;
});

$app->get('/songs/{songId}/raw/{rawType}', function (Request $request, Response $response, $args) {
	$songId = $args['songId'];
	$rawType = $args['rawType'];
	$ext = pathinfo($rawType, PATHINFO_EXTENSION);
	$fieldname = pathinfo($rawType, PATHINFO_FILENAME);
	$song = new Song($songId);
	$data = $song->getRawData($fieldname);
	if (!$data){
		header("HTTP/1.0 404 Not Found");
		die();
	} else {
		switch($ext){
			case 'png':
				$response = $response->withHeader('Content-type', 'image/png');
				break;
			case 'gif':
				$response = $response->withHeader('Content-type', 'image/gif');
				break;
			case 'pdf':
				$response = $response->withHeader('Content-type', 'application/pdf');
				break;
			case 'mid':
			case 'midi':
				$response = $response->withHeader('Content-type', 'audio/midi');
				break;
			default:
				$response = $response->withHeader('Content-type', 'application/octet-stream');
		}
		$response->getBody()->write($data);
		return $response;
	}
});

$app->post('/songs/{songId}/{rawType}', function (Request $request, Response $response, $args) {
	$songId = $args['songId'];
	$rawType = $args['rawType'];
	$song = new Song($songId);
	$rawdata = file_get_contents($_FILES['file']['tmp_name']);
	$song->setRawData($rawType, $rawdata);
	$song->save();
	return $response;
});

$app->get('/files/{fileId}', function (Request $request, Response $response, $args) {
	$fileId = $args['fileId'];
	$file = new File($fileId);
	$data = $file->getData();
	if ($data) {
		unset($data['rawData']);
		$response->getBody()->write(json_encode($data, JSON_NUMERIC_CHECK));
		return $response;
	} else {
		header("HTTP/1.0 404 Not Found");
		die();
	}
});

$app->get('/files/{fileId}/{filename}', function (Request $request, Response $response, $args) {
	$fileId = $args['fileId'];
	$file = new File($fileId);
	$data = $file->getData();
	if ($data) {
		$response = $response->withHeader('Content-Disposition', 'filename="'.$data['name'].'"')->withHeader('Content-type', $data['mime'] ?: 'application/octet-stream');
		$response->getBody()->write($data['rawData']);
		return $response;
	} else {
		header("HTTP/1.0 404 Not Found");
		die();
	}
});

$app->post('/files', function (Request $request, Response $response, $args) {
	$file = new File();
	if (!isset($_FILES['file'])) {
		throw new Exception('no file uploaded');
	}
	$qsa = $request->getQueryParams();
	$rawData = file_get_contents($_FILES['file']['tmp_name']);
	$data = [
		'songId' => intval($qsa['songId']),
		'type' => $qsa['type'],
		'name' => $_FILES['file']['name'],
		'mime' => $_FILES['file']['type'],
		'filesize' => $_FILES['file']['size'],
		'rawData' => $rawData,
	];
	$file->setData($data);
	$file->save();
	$response->getBody()->write($file->getId());
	return $response;
});

$app->delete('/files/{fileId}', function (Request $request, Response $response, $args) {
	$fileId = $args['fileId'];
	$file = new File($fileId);
	if ($file->delete()) {
		return $response->withStatus(204);
	} else {
		throw new Exception('Could not delete file');
	}
});

$app->get('/import/xml', function (Request $request, Response $response, $args) {
	$response = $response->withHeader('Content-type', 'text/html');

	$path = '../../data/sibelius_export/converted-xml';
	$files = scandir($path);

	foreach($files as $file){
		if (substr($file, -4) === '.xml'){
			var_dump($file);
			$data = file_get_contents($path.'/'.$file);
			$song = new Song();
			$song->loadFromXml($data);
			$title = trim(str_replace('.xml', '', $file));
			$song->setTitle($title);
			$song->save();
		}
	}
	return $response;
});

// for testing purposes
$app->get('/import/xml/{filename}', function (Request $request, Response $response, $args) {
	$filename = $args['filename'];

	$response = $response->withHeader('Content-type', 'text/html');

	$path = '../../data/sibelius_export/converted-xml';

	if (substr($filename, -4) === '.xml' && file_exists($path.'/'.$filename)){
		$data = file_get_contents($path.'/'.$filename);
		$song = new Song();
		$song->loadFromXml($data);
		$title = trim(str_replace('.xml', '', $filename));
		$song->setTitle($title);
		$song->save();
	} else {
		throw new Exception('File does not exist: '.$path.'/'.$filename);
	}
	return $response;
});

$app->get('/import/sib', function (Request $request, Response $response, $args) use (&$DB) {
	$response = $response->withHeader('Content-type', 'text/html');
	ini_set('max_execution_time', 300);

	$path = '../../data/sibelius_export/all';
	$files = scandir($path);

	foreach($files as $file){
		if (substr($file, -4) === '.sib'){
			$songtitle = substr($file, 0, -4);
			$songtitle = str_replace('_0001','',$songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAllAssociative("SELECT id FROM songs WHERE title = ?", array($songtitle));
			if(isset($ids[0]['id'])){
				var_dump($ids[0]['id']);
				$data = file_get_contents($path.'/'.$file);
				$song = new Song($ids[0]['id']);
				$song->setRawData('rawSIB', $data);
				$song->save();
			} else {
				var_dump("no song found for $songtitle");
			}
		}
	}
	return $response;
});

$app->get('/import/midi', function (Request $request, Response $response, $args) use (&$DB) {
	$response = $response->withHeader('Content-type', 'text/html');
	ini_set('max_execution_time', 300);

	$path = '../../data/sibelius_export/midi';
	$files = scandir($path);
	$count = 0;

	foreach($files as $file){
		if (substr($file, -4) === '.mid'){
			$songtitle = substr($file, 0, -4);
			$songtitle = normalizer_normalize($songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAllAssociative("SELECT id FROM songs WHERE title = ?", array($songtitle));
			if(isset($ids[0]['id'])){
				var_dump($ids[0]['id']);
				$data = file_get_contents($path.'/'.$file);
				$song = new Song($ids[0]['id']);
				$song->setRawData('rawMidi', $data);
				$song->save();
				$count++;
			} else {
				var_dump("no song found for: $songtitle");
			}
		}
	}
	var_dump("Files imported: $count");
	return $response;
});

$app->get('/import/notespdf', function (Request $request, Response $response, $args) use (&$DB) {
	$response = $response->withHeader('Content-type', 'text/html');
	ini_set('max_execution_time', 300);

	$files = [];
	$path = '../../data/Dateien_ohne_Texte';
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	$regexIterator = new RegexIterator($iterator, '/^.+\.pdf$/i', RecursiveRegexIterator::GET_MATCH);
	foreach($regexIterator as $file){
		$filepath = $file[0];
		$filename = basename($filepath);
		$files[] = [
			'filepath' => $filepath,
			'filename' => $filename
		];
	}


	//$files = scandir($path);
	$count = 0;
	$count_err = 0;
	$imported_ids = [];

	foreach($files as $file){
		if (substr($file['filename'], -4) === '.pdf'){
			$songtitle = substr($file['filename'], 0, -4);
			$songtitle = str_replace('_0001','',$songtitle);
			$songtitle = normalizer_normalize($songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAllAssociative("SELECT id FROM songs WHERE title = ?", array($songtitle));
			if(isset($ids[0]['id'])){
				var_dump($ids[0]['id']);
				$imported_ids[] = $ids[0]['id'];
				$data = file_get_contents($path.'/'.$file['filepath']);
				$song = new Song($ids[0]['id']);
				$song->setRawData('rawNotesPDF', $data);
				$song->save();
				$count++;
			} else {
				var_dump("no song found for: $songtitle '" . $file['filepath'] . "'");
				$count_err++;
			}
		}
	}
	var_dump("Imported IDS:", implode(',', $imported_ids));
	var_dump("Files imported: $count");
	var_dump("Errors: $count_err");

	var_dump("Not imported:");
	$not_imported = $DB->fetchAllAssociative("SELECT * FROM songs WHERE id NOT IN (".implode(',', $imported_ids).")");
	foreach ($not_imported as $song) {
		var_dump($song['id'] . ' - ' . $song['title']);
	}
	return $response;
});

$app->get('/import/png', function (Request $request, Response $response, $args) use (&$DB) {
	$response = $response->withHeader('Content-type', 'text/html');
	ini_set('max_execution_time', 300);

	$path = '../../data/sibelius_export/converted-png';
	$files = scandir($path);

	foreach($files as $file){
		if (substr($file, -4) === '.png'){

			$songtitle = substr($file, 0, -4);
			$songtitle = str_replace('_0001','',$songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAllAssociative("SELECT id FROM songs WHERE title = ?", array($songtitle));
			if(isset($ids[0]['id'])){
				var_dump($ids[0]['id']);
				$second_file = str_replace('_0001','_0002',$file);
				if ($file != $second_file && file_exists($path.'/'.$second_file)){

					// concat image 0001 and 0002 into a single image
					$image1 = imagecreatefrompng($path.'/'.$file);
					$image2 = imagecreatefrompng($path.'/'.$second_file);

					$w1 = imagesx($image1);
					$h1 = imagesy($image1);
					$w2 = imagesx($image2);
					$h2 = imagesy($image2);

					$newWidth = max($w1, $w2);
					$newHeight = $h1 + $h2;
					$newImage = imagecreatetruecolor($newWidth, $newHeight);
					$white = imagecolorallocate($newImage, 255, 255, 255);
					imagefill($newImage, 0, 0, $white);

					imagecopyresampled($newImage, $image1, 0, 0, 0, 0, $w1, $h1, $w1, $h1);
					imagecopyresampled($newImage, $image2, 0, $h1, 0, 0, $w2, $h2, $w2, $h2);

					ob_start();
					imagejpeg($newImage);
					$data = ob_get_clean();

				} else {
					$data = file_get_contents($path.'/'.$file);
				}

				$song = new Song($ids[0]['id']);
				$song->setRawData('rawNotesPNG', $data);
				$song->save();
			} else {
				var_dump("no song found for $songtitle");
			}
		}
	}
	return $response;
});

// show used chords
$app->get('/export/listchords', function (Request $request, Response $response, $args) use (&$DB) {
	$response = $response->withHeader('Content-type', 'text/html');
	$chords = [];

	$songs = $DB->fetchAllAssociative("SELECT id FROM songs WHERE releaseBook2024 = 1 or releaseApp2024 = 1");

	foreach($songs as $song_id){
		$model = new Song($song_id['id']);
		$clearedChoords = $model->getClearedChordList();
		foreach ($clearedChoords as $chord){
			if (isset($chords[$chord])) {
				$chords[$chord][] = $song_id['id'];
			} else {
				$chords[$chord] = [$song_id['id']];
			}
		}
	}
	ksort($chords);

	$html = '<table style="border-collapse: collapse; border: 1px solid #eee;">';
	$html .= '<tr><th style="border: 1px solid #eee;">Akkord</th><th style="border: 1px solid #eee;">Anzahl Songs</th><th style="border: 1px solid #eee;">Songs</th></tr>';
	foreach ($chords as $chord => $songIds) {
		$html .= '<tr><td valign="top" style="border: 1px solid #eee;">'.$chord.'</td><td valign="top" style="border: 1px solid #eee;">'.count($songIds).'</td><td valign="top" style="border: 1px solid #eee;">';
		foreach ($songIds as $songId) {
			$html .= '<a href="../../#/songs/'.$songId.'">'.$songId.'</a> ';
		}
		$html .= '</td></tr>';
	}
	$html .= '</table>';

	$response->getBody()->write($html);
	return $response;
});

// export json index for app
$app->get('/export/index', function (Request $request, Response $response, $args) use (&$DB) {
	$path = '../../../rondo-app/app/public/assets/songdata/songs/song-index.json';
	$songIndex = new SongIndex();
	$index = $songIndex->getSongIndexForApp();

	$json = json_encode($index, JSON_PRETTY_PRINT + JSON_NUMERIC_CHECK);
	if (file_exists($path)) {
		umask(0);
		file_put_contents($path, $json);
		@chmod($path, 0777);
	}
	$response = $response->withHeader('Content-type', 'application/json');
	$response->getBody()->write($json);
	return $response;
});

// export xml index for indesign
$app->get('/export/indesign.xml', function (Request $request, Response $response, $args) use (&$DB) {
	$xml = '';
	$songs = $DB->fetchAllAssociative("SELECT id FROM songs WHERE releaseBook2024 = 1 ORDER BY pageRondo2024 ASC");

	foreach($songs as $song_id) {
		$song = new Song($song_id['id']);
		$xml .= $song->getXML();
	}
	$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.PHP_EOL.'<Rondo>'.PHP_EOL.$xml.PHP_EOL.'</Rondo>';
	$response->getBody()->write($xml);

	$response = $response->withHeader('Content-Disposition', 'attachment; filename=indesign.xml');
	$response = $response->withHeader('Content-type', 'application/xml');

	return $response;
});


// export indesign files with notes
$app->get('/export/indesign.zip', function (Request $request, Response $response, $args) use (&$DB) {
	header('Content-Type: application/zip');

	$xml = '';

	# create a new zipstream object
	$zip = new ZipStream\ZipStream(outputName: 'rondo_indesign_'.date('Y-m-d').'.zip');

	$songIds = $DB->fetchAllAssociative("SELECT id FROM songs WHERE releaseBook2024 = 1 ORDER BY pageRondo2024 ASC");

	foreach($songIds as $songId){
		$song = new Song($songId['id']);
		$xml .= $song->getXML();

		$data = $song->getData();

		// generate pdf
		if ($data['rawNotesPDF']){
			$zip->addFile('pdf/noten_'.$songId['id'].'.pdf', $data['rawNotesPDF']);
		}
	}

	$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.PHP_EOL.'<Rondo>'.PHP_EOL.$xml.PHP_EOL.'</Rondo>';


	$zip->addFile('rondo.xml', $xml);

	# finish the zip stream
	$zip->finish();
	die();

});

// export html files & images for app
$app->get('/export/html', function (Request $request, Response $response, $args) use (&$DB) {
	umask(0);
	$path = '../../../rondo-app/app/public/assets/songdata/songs/';

	if (!file_exists($path)) {
		die('folder does not exist: ' . $path);
	}

	$songIndex = new SongIndex();
	$songIds = $songIndex->getAppSongIds();

	foreach($songIds as $songId){
		$song = new Song($songId['id']);

		// generate html
		$html = $song->getHtml(true);
		$filepath = $path.'html/'.$songId['id'].'.html';
		file_put_contents($filepath, $html);

		// generate image
		$data = $song->getData();
		if ($data['rawImage']){
			// convert to gif
			ob_start();
			try {
				$tmp = imagecreatefromstring($data['rawImage']);
				if (!$tmp) {
					throw new Exception('wrong image format!');
				}
				imagegif($tmp);
				$image = ob_get_clean();
				$imagepath = $path.'images/'.$songId['id'].'.gif';
				file_put_contents($imagepath, $image);
			} catch (Exception $exception) {
				var_dump('Image Problem with Song: ' . $songId['id'], $exception->getMessage());
			}
		}

		// generate pdf
		if ($data['rawNotesPDF']){
			$pdfpath = $path.'pdfs/'.$songId['id'].'.pdf';
			file_put_contents($pdfpath, $data['rawNotesPDF']);
		} else {
			echo 'No pdf file for song '.$songId['id'].'<br>';
		}

		// export midi
		if ($data['rawMidi']){
			$midipath = $path.'midi/'.$songId['id'].'.mid';
			file_put_contents($midipath, $data['rawMidi']);
		} else {
			echo 'No midi file for song '.$songId['id'].'<br>';
		}
	}
	echo count($songIds)." Songs exportiert.";
	return $response;
});

// export html files & images as zip
$app->get('/export/zip', function (Request $request, Response $response, $args) use (&$DB) {
	header('Content-Type: application/zip');

	# create a new zipstream object
	$zip = new ZipStream\ZipStream(outputName: 'rondo_data_'.date('Y-m-d').'.zip');

	$songIndex = new SongIndex();
	$songIds = $songIndex->getAppSongIds();

	foreach($songIds as $songId){
		$song = new Song($songId['id']);

		// generate html
		$html = $song->getHtml(true);
		$zip->addFile('html/'.$songId['id'].'.html', $html);

		$data = $song->getData();

		// generate image
		if ($data['rawImage']){
			// convert to gif
			try {
				ob_start();
				$tmp = @imagecreatefromstring($data['rawImage']);
				if (!$tmp) {
					throw new Exception('wrong image format!');
				}
				imagegif($tmp);
				$image = ob_get_clean();
				$zip->addFile('images/'.$songId['id'].'.gif', $image);
			} catch (Exception $exception) {
//				var_dump('Image Problem with Song: ' . $songId['id'], $exception->getMessage());
//				die();
			}
		}

		// generate pdf
		if ($data['rawNotesPDF']){
			$zip->addFile('pdfs/'.$songId['id'].'.pdf', $data['rawNotesPDF']);
		}

		// generate midi
		if ($data['rawMidi']){
			$zip->addFile('midi/'.$songId['id'].'.mid', $data['rawMidi']);
		}
	}

	// song index
	$index = $songIndex->getSongIndexForApp();
	$json = json_encode($index, JSON_PRETTY_PRINT + JSON_NUMERIC_CHECK);
	$zip->addFile('song-index.json', $json);

	# finish the zip stream
	$zip->finish();
	die();
});

$app->get('/export/bookindex.csv', function (Request $request, Response $response, $args) use (&$DB) {

	setlocale(LC_CTYPE, 'de_DE.UTF8');

	$sortable = [];
	$songs = $DB->fetchAllAssociative("SELECT title, alternativeTitles, pageRondoRed, pageRondoBlue, pageRondoGreen, pageRondo2017, pageRondo2021, pageRondo2024 FROM songs WHERE releaseBook2024 = 1");

	foreach ($songs as $song) {

		// add song by main title
		$song['isMainTitle'] = 1;
		$sortable[mb_strtoupper($song['title'])] = $song;

		// add songs by alternative titles
		$song['isMainTitle'] = 0;
		if (strlen($song['alternativeTitles'])) {
			$titles = explode("\n", $song['alternativeTitles']);
			foreach ($titles as $title) {
				if (strlen(trim($title))) {
					$sortable[trim($title)] = $song;
				}
			}
		}
	}

	ksort($sortable, SORT_STRING | SORT_FLAG_CASE);

	$response = $response->withHeader('Content-Disposition', 'attachment; filename=bookindex.csv');
	$response = $response->withHeader('Content-Type', 'text/csv');

	$csv = '"Liedtitel","Seite 2024","Seite mova","Seite Orange","Seite Grün","Seite Blau","Seite Rot","Haupttitel"' . "\n";
	foreach ($sortable as $title => $song) {
		$csv .= '"'.$title.'",'.$song['pageRondo2024'].','.$song['pageRondo2021'].','.$song['pageRondo2017'].','.$song['pageRondoGreen'].','.$song['pageRondoBlue'].','.$song['pageRondoRed'].','.($song['isMainTitle'] ? '"Ja"' : '"Nein"') . "\n";
	}

	$response->getBody()->write($csv);
	return $response;
});

$app->get('/export/songs.csv', function (Request $request, Response $response, $args) use (&$DB) {

	setlocale(LC_CTYPE, 'de_DE.UTF8');

	$songs = $DB->fetchAllAssociative("SELECT id, title, alternativeTitles, interpret, pageRondoRed, pageRondoBlue, pageRondoGreen, pageRondo2017, pageRondo2021, pageRondo2024, releaseApp2017, releaseApp2022, releaseApp2024, releaseBook2017, releaseBook2021, releaseBook2024, status, copyrightStatusApp, copyrightStatusBook2017, copyrightStatusBook2021, copyrightStatusBook2024, license, license_type, youtubeLink FROM songs ORDER BY title ASC");

	$response = $response->withHeader('Content-Disposition', 'attachment; filename=songs.csv');
	$response = $response->withHeader('Content-Type', 'text/csv');

	$csv = '"id","Titel","Alternative Titel","Interpret","Seite Rondo Rot","Seite Rondo Blau","Seite Rondo Gruen","Seite Rondo 2017","Seite Rondo mova","Seite Rondo 2024","App 2017","App 2022","App 2024","Buch 2017","Buch mova","Buch 2024","Status","Copyright Status App","Copyright Status Buch 2017","Copyright Status Buch 2021","Copyright Status Buch 2024","Lizenz","Lizentyp","Youtube Link",' . "\n";
	foreach ($songs as $song) {
		foreach ($song as $key => $value) {
			if ($value !== null) {
				$value = str_replace('"', '""', $value);
				$value = str_replace("\n", ' ', $value);
			} else {
				$value = '';
			}
			$csv .= '"'.$value.'",';
		}
		$csv .= "\n";
	}
	$response->getBody()->write($csv);
	return $response;
});

$app->get('/export/songs.xlsx', function (Request $request, Response $response, $args) use (&$DB) {

	setlocale(LC_CTYPE, 'de_DE.UTF8');

	$songs = $DB->fetchAllAssociative("SELECT id, title, alternativeTitles, interpret, pageRondoRed, pageRondoBlue, pageRondoGreen, pageRondo2017, pageRondo2021, pageRondo2024, releaseApp2017, releaseApp2022, releaseApp2024, releaseBook2017, releaseBook2021, releaseBook2024, status, copyrightStatusApp, copyrightStatusBook2017, copyrightStatusBook2021, copyrightStatusBook2024, license, license_type, copyrightInfoApp, copyrightInfoBook, copyrightPublisher, copyrightContact, license_type_app, copyrightCommentApp, licenseAppUntil, licenseAppUntilIndefinite, youtubeLink, comments FROM songs ORDER BY title ASC");
	$titles = ["ID","Titel","Alternative Titel","Interpret","Seite Rondo Rot","Seite Rondo Blau","Seite Rondo Gruen","Seite Rondo 2017","Seite Rondo mova","Seite Rondo 2024","App (bis 2022)","App (ab 2022)","App (ab 2024)","Buch 2017","Buch mova","Buch 2024","Status","Copyright Status App","Copyright Status Buch 2017","Copyright Status Buch 2021","Copyright Status Buch 2024","Lizenz","Lizentyp","Copyright Info App","Copyright Info Buch","Verlag","Copyright Kontakt","Lizenz-Typ App","Bemerkung Copyright App","Copyright App bis","Copyright App unbestimmte Dauer","Youtube Link","Kommentare"];

	$data = [];
	$data[] = $titles;
	foreach ($songs as $song) {
		$row = [];
		foreach ($song as $key => $value) {
			$row[] = $value;
		}
		$data[] = $row;
	}

	$spreadsheet = new Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->fromArray($data);
	$sheet->getColumnDimension('B')->setWidth(30);
	$sheet->getColumnDimension('C')->setWidth(30);
	$sheet->getColumnDimension('D')->setWidth(20);
	$sheet->getColumnDimension('X')->setWidth(50);
	$sheet->getColumnDimension('Y')->setWidth(50);
	$sheet->getColumnDimension('Z')->setWidth(20);
	$sheet->getColumnDimension('AC')->setWidth(50);
	$sheet->getColumnDimension('AA')->setWidth(50);
	$sheet->getColumnDimension('AG')->setWidth(200);

	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="Songs.xlsx"');
	header('Cache-Control: max-age=0');

	$writer = new Xlsx($spreadsheet);
	$writer->save('php://output');
});

$app->get('/validate2024', function (Request $request, Response $response, $args) use (&$DB) {
	ini_set('max_execution_time', 300);
	$response = $response->withHeader('Content-type', 'text/html');
	echo '<h1>Fehlerprüfung</h1>';

	$errors = [];
	$reportError = function($category, $msg, $data) use (&$errors) {
		if (!isset($errors[$category])) {
			$errors[$category] = [];
		}
		$errors[$category][] = [$data, $msg];
	};

	$songIds = $DB->fetchAllAssociative("SELECT id FROM songs WHERE releaseBook2024 = 1 or releaseApp2024 = 1 order by title ASC");
	foreach ($songIds as $songId) {
		$song = new Song($songId['id']);
		$data = $song->getData();

		// validate pdf
		if (!$data['rawNotesPDF']) {
			$reportError('PDF', 'Kein PDF hochgeladen', $data);
		}

		// validate sibelius
		if (!$data['rawSIB']) {
			$reportError('Sibelius', 'Keine Sibelius Datei hochgeladen', $data);
		}

		// validate songtext
		if ($data['text'] && str_contains($data['text'], '   ')) {
			$reportError('Leerzeichen', 'Drei oder mehr aufeinanderfolgende Leerzeichen in Songtext gefunden', $data);
		}
		if ($data['text'] && str_contains($data['text'], "\n\n\n")) {
			$reportError('Newlines', 'Drei oder mehr aufeinanderfolgende Zeilenumbrüche in Songtext gefunden', $data);
		}

		// validate page number
		if ($data['releaseBook2024'] && !$data['pageRondo2024']) {
			$reportError('Seitenzahlen', 'Keine Seitenzahl für Buch 2024 eingetragen', $data);
		}

		// validate license status
		if ($data['license'] == 'UNKNOWN') {
			$reportError('Lizenz', 'Lizenz ist noch "Unbekannt"', $data);
		}

		// validate language
		if (!$data['lang']) {
			$reportError('Sprache', 'Keine Sprache ausgewählt', $data);
		}

		// validate mood
		if (!$data['mood']) {
			$reportError('Stimmung', 'Keine Stimmung eingetragen', $data);
		}

		// validate song status
		if ($data['status'] != 'DONE') {
			$reportError('Status', 'Lied Status ist noch nicht "Fertig": '.$data['status'], $data);
		}

		// app crosscheck
		if ($data['releaseBook2024'] && !$data['releaseApp2024']) {
			$reportError('App', 'Lied ist ausgewählt für ins Buch, aber nicht für App', $data);
		}

		// License check
		if ($data['copyrightStatusApp'] === 'NO_LICENSE' && $data['releaseApp2024']) {
			$reportError('App Lizenz', 'Keine Lizenz für App erhalten, aber für App ausgewählt', $data);
		}
		if ($data['copyrightStatusBook2024'] === 'NO_LICENSE' && $data['releaseBook2024']) {
			$reportError('Buch Lizenz', 'Keine Lizenz für Buch erhalten, aber für Buch ausgewählt', $data);
		}

		// App only
		if ($data['releaseApp2024']) {

			// validate files
			if (!$data['rawImage']) {
				$reportError('App Bild', 'Kein App Bild hochgeladen', $data);
			}
			if (!$data['rawMidi']) {
				$reportError('App Midi', 'Keine Midi Datei hochgeladen', $data);
			}

			// youtube link
			if (!$data['youtubeLink']) {
				$reportError('Youtube', 'Kein Youtube Link eingetragen', $data);
			}

			// validate chords
			$chords = $song->getClearedChordList();
			foreach ($chords as $chord) {
				if (!in_array($chord, AVAILABLE_CHORDS)) {
					$reportError('Akkordzeichnung', 'Akkord verwendet der keine Zeichnung (in der App) hat: '. $chord, $data);
				}
			}

			// validate license status
			if ($data['license'] != 'FREE' && $data['copyrightStatusApp'] != 'DONE') {
				$reportError('App Copyright', 'Copyright Status für App ist noch nicht "Fertig": ' . $data['copyrightStatusApp'], $data);
			}
		}

		// Book only
		if ($data['releaseBook2024']) {
			// validate license status
			if ($data['license'] != 'FREE' && $data['copyrightStatusBook2024'] != 'DONE') {
				$reportError('Buch Copyright', 'Copyright Status für Buch noch nicht "Fertig": ' . $data['copyrightStatusBook2024'], $data);
			}
		}
	}

	foreach ($errors as $category => $list) {
		echo '<div><a href="#'.$category.'">'.$category.' ('.count($list).')</a></div>';
	}

	foreach ($errors as $category => $list) {
		echo '<h2 id="'.$category.'">'.$category.' ('.count($list).')</h2>';
		foreach ($list as $item) {
			echo '<b style="display: inline-block; width: 300px; overflow: hidden">';
			echo '<a href="..#/songs/'.$item[0]['id'].'">'.$item[0]['title'].'</a>:';
			echo '</b>';
			echo $item[1].'<br>';
		}
	}
	return $response;
});

$app->run();
