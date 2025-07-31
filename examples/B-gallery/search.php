<?php declare(strict_types=1);

$path = $_GET['path'] ?? '.';
$string = $_GET['q'] ?? '';

if (!is_dir($path) || strlen($string) < 3) exit;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

set_time_limit(0);

while (ob_get_level()) ob_end_clean();

$counter = findPathsContainingString($path, $string, 20);
sendMessage(['result' => 'Found: ' . strval($counter)]);
exit();

//functions

function findPathsContainingString(string $directory, string $match, int $max = 0): int
{
	static $counter = 0;

	if (connection_aborted()) exit;
	if (!is_dir($directory)) return $counter;
	if ($max && $counter >= $max) return $counter;

	$match = remove_diacritics($match);

	try {
		$iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
		foreach ($iterator as $fileinfo) {
			if ($fileinfo->isDir())
			{
				if (stripos(remove_diacritics($fileinfo->getFilename()), $match) !== false) //case insensitive
				{
					sendMessage(['path' => $fileinfo->getPathname()]);
					$counter++;
				}
				findPathsContainingString($fileinfo->getPathname(), $match, $max); //if ($fileinfo->isDir())
			}
		}
	} catch (UnexpectedValueException $e) {
		sendMessage(['error' => "Error reading directory {$directory}: " . $e->getMessage()]);
	}

	return $counter;
}

function sendMessage(array $data): void
{
	if (connection_aborted()) exit;

	echo 'data: ' . json_encode($data) . "\n\n"; // "\n\n" = JS SSE end of event (must be!!!) 
	ob_flush();
	flush();

	usleep(100000); //100ms slow search simulation
}

function remove_diacritics($text)
{
	if(is_callable('iconv')) return iconv('UTF-8', 'ASCII//TRANSLIT', $text);
	else
	{
		$replacements = array(
			'á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'í' => 'i',
			'ĺ' => 'l', 'ľ' => 'l', 'ň' => 'n', 'ó' => 'o', 'ô' => 'o', 'ŕ' => 'r',
			'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ý' => 'y', 'ž' => 'z',

			'Á' => 'A', 'Ä' => 'A', 'Č' => 'C', 'Ď' => 'D', 'É' => 'E', 'Í' => 'I',
			'Ĺ' => 'L', 'Ľ' => 'L', 'Ň' => 'N', 'Ó' => 'O', 'Ô' => 'O', 'Ŕ' => 'R',
			'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ú' => 'U', 'Ý' => 'Y', 'Ž' => 'Z',
		);
		return strtr($text, $replacements);
	}
}
