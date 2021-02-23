<?php

require '../../vendor/autoload.php';

require '_conf.php';
require 'models/CrdParser.php';
require 'models/Song.php';
require 'models/SongIndex.php';


use Doctrine\DBAL\DriverManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$DB = DriverManager::getConnection($SQL_CREDENTIALS, new \Doctrine\DBAL\Configuration());

define ('EOL', PHP_EOL);


$app = new \Slim\Slim();
$app->config('debug', true);
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/songs', function () use(&$DB) {
	$si = new SongIndex();
	echo json_encode($si->getSongIndex(), JSON_NUMERIC_CHECK);
});

$app->get('/songs/:songId', function ($songId) {
	$song = new Song($songId);
	$data = $song->getData();

	function startsWithRaw($haystack, $needle = "raw") {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	foreach($data as $fieldname => $value){
		if (startsWithRaw($fieldname)){
			$data[$fieldname.'Size'] = strlen($data[$fieldname]);
			unset($data[$fieldname]);
		}
	}

	echo json_encode($data, JSON_NUMERIC_CHECK);
});

$app->put('/songs/:songId', function ($songId) use ($app) {
	$song = new Song($songId);
	$data = json_decode($app->request->getBody(), true);
	$song->setData($data)->save();
	return true;
});

$app->post('/songs', function () use ($app) {
	$song = new Song();
	$data = json_decode($app->request->getBody(), true);
	$song->setTitle(trim($data['title']));
	$song->setInterpret(trim($data['interpret']));
	$song->save();
	return $song->getId();
});

$app->get('/songs/:songId/html', function ($songId) use ($app) {
	$app->contentType('text/html');
	$song = new Song($songId);
	echo $song->getHtml();
});

// testing only
$app->get('/songs/:songId/crd', function ($songId) use ($app) {
	$app->contentType('text/html');

	$song = new Song($songId);
	$result = CrdParser::run($song->getData()['text']);
	var_dump($result);
});

$app->get('/songs/:songId/raw/:rawType', function ($songId, $rawType) use ($app) {
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
				$app->contentType('image/png');
				break;
			case 'gif':
				$app->contentType('image/gif');
				break;
			case 'pdf':
				$app->contentType('application/pdf');
				break;
			case 'mid':
			case 'midi':
				$app->contentType('audio/midi');
				break;
			default:
				$app->contentType('application/octet-stream');
		}
		echo $data;
	}
});

$app->post('/songs/:songId/:rawType', function ($songId, $rawType) use ($app) {
	$song = new Song($songId);
	$rawdata = file_get_contents($_FILES['file']['tmp_name']);
	$song->setRawData($rawType, $rawdata);
	$song->save();
});

$app->get('/import/xml', function () use ($app) {
	$app->contentType('text/html');

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
});

// for testing purposes
$app->get('/import/xml/:filename', function ($filename) use ($app) {
	$app->contentType('text/html');

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
});

$app->get('/import/sib', function () use ($app, &$DB) {
	$app->contentType('text/html');
	ini_set('max_execution_time', 300);

	$path = '../../data/sibelius_export/all';
	$files = scandir($path);

	foreach($files as $file){
		if (substr($file, -4) === '.sib'){
			$songtitle = substr($file, 0, -4);
			$songtitle = str_replace('_0001','',$songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAll("SELECT id FROM songs WHERE title = ?", array($songtitle));
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
});

$app->get('/import/midi', function () use ($app, &$DB) {
	$app->contentType('text/html');
	ini_set('max_execution_time', 300);

	$path = '../../data/sibelius_export/midi';
	$files = scandir($path);
	$count = 0;

	foreach($files as $file){
		if (substr($file, -4) === '.mid'){
			$songtitle = substr($file, 0, -4);
			$songtitle = normalizer_normalize($songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAll("SELECT id FROM songs WHERE title = ?", array($songtitle));
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
});

$app->get('/import/notespdf', function () use ($app, &$DB) {
	$app->contentType('text/html');
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
			$ids = $DB->fetchAll("SELECT id FROM songs WHERE title = ?", array($songtitle));
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
	$not_imported = $DB->fetchAll("SELECT * FROM songs WHERE id NOT IN (".implode(',', $imported_ids).")");
	foreach ($not_imported as $song) {
		var_dump($song['id'] . ' - ' . $song['title']);
	}

});

$app->get('/import/png', function () use ($app, &$DB) {
	$app->contentType('text/html');
	ini_set('max_execution_time', 300);

	$path = '../../data/sibelius_export/converted-png';
	$files = scandir($path);

	foreach($files as $file){
		if (substr($file, -4) === '.png'){

			$songtitle = substr($file, 0, -4);
			$songtitle = str_replace('_0001','',$songtitle);
			//var_dump($songtitle);
			$ids = $DB->fetchAll("SELECT id FROM songs WHERE title = ?", array($songtitle));
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
});

// show used chords
$app->get('/export/listchords', function () use ($app, &$DB) {
	$app->contentType('text/html');
	$chords = [];

	$songs = $DB->fetchAll("SELECT id FROM songs WHERE releaseBook2021 = 1");

	foreach($songs as $song_id){
		$model = new Song($song_id['id']);
		$chords = array_merge($chords, $model->getClearedChordList());
	}
	$chords = array_unique($chords);
	sort($chords);
	echo implode('<br>',$chords);

});

// export json index for app
$app->get('/export/index', function () use ($app, &$DB) {
	$path = '../../../rondo-app/app/src/assets/songdata/songs/song-index.json';
	$songIndex = new SongIndex();
	$index = $songIndex->getSongIndexForApp();

	$json = json_encode($index, JSON_PRETTY_PRINT + JSON_NUMERIC_CHECK);
	if (file_exists($path)) {
		umask(0);
		file_put_contents($path, $json);
		@chmod($path, 0777);
	}
	echo $json;
});

// export xml index for indesign
$app->get('/export/indesign.xml', function () use ($app, &$DB) {
	$xml = '';
	$songs = $DB->fetchAll("SELECT id FROM songs WHERE releaseBook2021 = 1 ORDER BY pageRondo2017 ASC");

	foreach($songs as $song_id) {
		$song = new Song($song_id['id']);
		$xml .= $song->getXML();
	}
	$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.PHP_EOL.'<Rondo>'.PHP_EOL.$xml.PHP_EOL.'</Rondo>';

	$app->response->headers->set('Content-Disposition', 'attachment; filename=indesign.xml');
	$app->response->headers->set('Content-Type', 'application/xml');

	echo $xml;
});


// export indesign files with notes
$app->get('/export/indesign.zip', function () use ($app, &$DB) {
	$app->response->headers->set('Content-Type', 'application/zip');

	$xml = '';

	# create a new zipstream object
	$zip = new ZipStream\ZipStream('rondo_indesign_'.date('Y-m-d').'.zip');

	$songIds = $DB->fetchAll("SELECT id FROM songs WHERE releaseBook2021 = 1 ORDER BY pageRondo2017 ASC");

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

});

// export html files & images for app
$app->get('/export/html', function () use ($app, &$DB) {
	umask(0);
	$path = '../../../rondo-app/app/src/assets/songdata/songs/';

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
		//chmod($filepath, 0777);

		// generate image
		$data = $song->getData();
		if ($data['rawImage']){
			// convert to gif
			ob_start();
			try {
				imagegif(imagecreatefromstring($data['rawImage']));
				$image = ob_get_clean();
				$imagepath = $path.'images/'.$songId['id'].'.gif';
				file_put_contents($imagepath, $image);
			} catch (Exception $exception) {
				var_dump('Image Problem with Song: ' . $songId['id'], $exception->getMessage());
			}
			//chmod($imagepath, 0777);
		}
	}
	echo count($songIds)." Songs exportiert.";
});

// export html files & images as zip
$app->get('/export/zip', function () use ($app, &$DB) {
	# create a new zipstream object
	$zip = new ZipStream\ZipStream('rondo_data_'.date('Y-m-d').'.zip');

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
			ob_start();
			imagegif(imagecreatefromstring($data['rawImage']));
			$image = ob_get_clean();
			$zip->addFile('images/'.$songId['id'].'.gif', $image);
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
});

$app->get('/export/bookindex.csv', function () use ($app, &$DB) {

	setlocale(LC_CTYPE, 'de_DE.UTF8');

	$sortable = [];
	$songs = $DB->fetchAll("SELECT title, alternativeTitles, pageRondoRed, pageRondoBlue, pageRondoGreen, pageRondo2017, pageRondo2021 FROM songs WHERE releaseBook2021 = 1");

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

	$app->response->headers->set('Content-Disposition', 'attachment; filename=bookindex.csv');
	$app->response->headers->set('Content-Type', 'text/csv');

	echo '"Liedtitel","Seite 2021","Seite 2017","Seite Grün","Seite Blau","Seite Rot","Haupttitel"' . "\n";
	foreach ($sortable as $title => $song) {
		echo '"'.$title.'",'.$song['pageRondo2021'].','.$song['pageRondo2017'].','.$song['pageRondoGreen'].','.$song['pageRondoBlue'].','.$song['pageRondoRed'].','.($song['isMainTitle'] ? '"Ja"' : '"Nein"') . "\n";
	}
});

$app->get('/export/songs.csv', function () use ($app, &$DB) {

	setlocale(LC_CTYPE, 'de_DE.UTF8');

	$songs = $DB->fetchAll("SELECT id, title, alternativeTitles, interpret, pageRondoRed, pageRondoBlue, pageRondoGreen, pageRondo2017, pageRondo2021, releaseApp2017, releaseBook2017, releaseBook2021, status, copyrightStatusApp, copyrightStatusBook2017, copyrightStatusBook2021, license, license_type, youtubeLink FROM songs ORDER BY title ASC");

	$app->response->headers->set('Content-Disposition', 'attachment; filename=songs.csv');
	$app->response->headers->set('Content-Type', 'text/csv');

	echo '"id","Titel","Alternative Titel","Interpret","Seite Rondo Rot","Seite Rondo Blau","Seite Rondo Gruen","Seite Rondo 2017","Seite Rondo 2021","App","Buch 2017","Buch 2021","Status","Copyright Status App","Copyright Status Buch 2017","Copyright Status Buch 2021","Lizenz","Lizentyp","Youtube Link",' . "\n";
	foreach ($songs as $song) {
		foreach ($song as $key => $value) {
			$value = str_replace('"', '""', $value);
			$value = str_replace("\n", ' ', $value);
			echo '"'.$value.'",';
		}
		echo "\n";
	}
});

$app->get('/export/songs.xlsx', function () use ($app, &$DB) {

	setlocale(LC_CTYPE, 'de_DE.UTF8');

	$songs = $DB->fetchAll("SELECT id, title, alternativeTitles, interpret, pageRondoRed, pageRondoBlue, pageRondoGreen, pageRondo2017, pageRondo2021, releaseApp2017, releaseBook2017, releaseBook2021, status, copyrightStatusApp, copyrightStatusBook2017, copyrightStatusBook2021, license, license_type, copyrightInfoApp, copyrightInfoBook, copyrightContact, youtubeLink, comments FROM songs ORDER BY title ASC");
	$titles = ["ID","Titel","Alternative Titel","Interpret","Seite Rondo Rot","Seite Rondo Blau","Seite Rondo Gruen","Seite Rondo 2017","Seite Rondo 2021","App","Buch 2017","Buch 2021","Status","Copyright Status App","Copyright Status Buch 2017","Copyright Status Buch 2021","Lizenz","Lizentyp","Copyright Info App","Copyright Info Buch","Copyright Kontakt","Youtube Link","Kommentare"];

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
	$sheet->getColumnDimension('S')->setWidth(50);
	$sheet->getColumnDimension('T')->setWidth(50);
	$sheet->getColumnDimension('U')->setWidth(50);
	$sheet->getColumnDimension('V')->setWidth(200);

	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="Songs.xlsx"');
	header('Cache-Control: max-age=0');

	$writer = new Xlsx($spreadsheet);
	$writer->save('php://output');
});

$app->get('/validate', function () use ($app, &$DB) {
	ini_set('max_execution_time', 300);
	$app->contentType('text/html');
	echo '<pre>';

	function invalid($msg, $data = []) {
		echo '<b style="display: inline-block; width: 300px; overflow: hidden">'.$data['title'].':</b>';
		echo $msg.'<br>';
	}

	$available_chord_icons = ["A","A7","Am","Am7","B","C","C7","D","D7","Dm","E","E7","Em","F-bar","F","Fis","Fism","Fm","G","G7","Gm","H7","Hm","Hm7"];

	$songIds = $DB->fetchAll("SELECT id FROM songs WHERE releaseBook2021 = 1 or releaseApp2017 = 1 order by title ASC");
	foreach ($songIds as $songId) {
		$song = new Song($songId['id']);
		$data = $song->getData();

		// validate pdf
		if (!$data['rawNotesPDF']) {
			invalid('Kein PDF', $data);
		}

		// validate songtext
		//if (strpos($data['text'], '   ') !== false) {
		//	invalid('Drei oder mehr aufeinanderfolgende Leerzeichen in Songtext', $data);
		//}
		//if (strpos($data['text'], "\n\n\n") !== false) {
		//	invalid('Drei oder mehr aufeinanderfolgende Zeilenumbrüche in Songtext', $data);
		//}

		// validate page number
		if ($data['releaseBook2021'] && !$data['pageRondo2021']) {
			invalid('Keine Seitenzahl für Buch 2021', $data);
		}

		// validate license status
		if ($data['license'] == 'UNKNOWN') {
			invalid('Lizenz is UNKNOWN', $data);
		}

		// validate song status
		if ($data['status'] != 'DONE') {
			invalid('Lied Status ist noch nicht gut: '.$data['status'], $data);
		}

		// App only
		if ($data['releaseApp2017']) {

			// validate files
			if (!$data['rawImage']) {
				invalid('Kein App Bild', $data);
			}
			if (!$data['rawMidi']) {
				invalid('Kein Midi', $data);
			}

			// validate chords
			$chords = $song->getClearedChordList();
			foreach ($chords as $chord) {
				if (!in_array($chord, $available_chord_icons)) {
					invalid('Akkord verwendet der keine Zeichnung hat: '. $chord, $data);
				}
			}

			// validate license status
			if ($data['license'] != 'FREE' && $data['copyrightStatusApp'] != 'DONE') {
				invalid('Copyright Status für App noch nicht gut: ' . $data['copyrightStatusApp'], $data);
			}
		}


		// Book only
		if ($data['releaseBook2021']) {
			// validate license status
			if ($data['license'] != 'FREE' && $data['copyrightStatusBook2021'] != 'DONE') {
				invalid('Copyright Status für Buch noch nicht gut: ' . $data['copyrightStatusBook2021'], $data);
			}
		}

	}
});

$app->run();
