<?php

namespace App\Anet\Helpers;

use App\Anet\DB;

/**
 * **Loger** -- helper static class for working with logs (save to DBm save to XML, archive)
 */
final class Loger
{
    use FileSystemTrait;

    /**
     * @var string `private` path to logs directory
     */
    private const LOGS_PATH = LOGS_PATH;
    /**
     * @var string `private` path with base name to proccess logs
     */
    private const LOGS_PROCCESS_BASE_NAME = '/proccess/proccess_report';
    /**
     * @var string `private` path with base name to runtime logs
     */
    private const RUNTIME_LOGS_NAME = '/runtime/logs';
    /**
     * @var string `private` name of root xml tag
     */
    private const XML_ROOT_TAG = 'body';
    /**
     * @var string `private` name of proccess statistic xml tag
     */
    private const XML_PROCCESS_GLOBAL_TAG = 'global';
    /**
     * @var string `private` name of proccess detail xml tag
     */
    private const XML_PROCCESS_DETAIL_TAG = 'detail';
    /**
     * @var string `private` name of proccess node xml tag
     */
    private const XML_PROCCESS_NODE_TAG = 'proccessing';
    /**
     * @var string `private` prefix of archived files
     */
    private const ARCHIVE_POSTFIX = '-old';

    /**
     * **method** archive logs directory by directory category name
     * @param string $category name of logs directory
     * @return void
     */
    public static function archiveLogsByCategory(string $category) : void
    {
        self::archiveLogsRecursive(self::LOGS_PATH . "/$category");
    }

    /**
     * **Method** save script output to runtime log file
     * @param string $category name of logs directory
     * @param string $message output text to save
     * @return void
     */
    public static function print(string $category, string $message) : void
    {
        $logsFile = self::LOGS_PATH . self::RUNTIME_LOGS_NAME;
        self::makeDirectory(dirname($logsFile));
        file_put_contents($logsFile, ((new \DateTime())->format('Y-m-d H:i:s')) . " -- $category -- $message\n", FILE_APPEND);
    }

    /**
     * **Method** save logs of proccess to xml file
     * @param string $category name of logs directory
     * @param array $globalProccess data of global proccess statistic
     * @param array $detailProccess data of proccess detail
     * @return bool return false if globalProccess or detailProccess are empty else - true after success saving
     */
    public static function loggingProccess(string $category, array $globalProccess, array $detailProccess) : bool
    {
        if (empty($globalProccess) || empty($detailProccess)) {
            return false;
        }

        $fileName = self::initialLogsXML("/$category" . self::LOGS_PROCCESS_BASE_NAME);
        self::saveProccessToXML($fileName, $globalProccess, $detailProccess);
        self::formatLogsXML($fileName);

        return true;
    }

    /**
     * **Method** save standart logs to xml file
     * @param string $category name of logs directory
     * @param array $data detail data of logs
     * @param string $mode name of dir (with prefix 's') and xml root node
     * @return bool return false if data is empty else - true after success saving
     */
    public static function logging(string $category, array $data, string $mode) : bool
    {
        if (empty($data)) {
            return false;
        }

        $fileName = self::initialLogsXML("/$category/{$mode}s/report");
        self::saveToXML($fileName, $data, $mode);
        self::formatLogsXML($fileName);

        return true;
    }

    /**
     * **Method** save logs with global proccess statistics to named database
     * @param string $category name of logs directory
     * @return void
     */
    public static function saveProccessToDB(string $category) : void
    {
        $directory = dirname(self::LOGS_PATH . "/$category" . self::LOGS_PROCCESS_BASE_NAME);

        foreach (scandir($directory) as $file) {
            if (is_file("$directory/$file") && mb_strpos($file, self::ARCHIVE_POSTFIX) === false) {
                self::saveProccessGlobal("$directory/$file", $category);
            }
        }
    }

    /**
     * **Method** save logs directory by category name to DB
     * @param string $category name of logs directory
     * @param string $logs base name of logs type directory
     * @param string $database name of database
     * @return void
     */
    public static function saveToDB(string $category, string $logs, string $database) : void
    {
        $directory = self::LOGS_PATH . "/$category/{$logs}s";

        foreach (scandir($directory) as $file) {
            if (is_file("$directory/$file") && mb_strpos($file, self::ARCHIVE_POSTFIX) === false) {
                self::saveXMLToDB("$directory/$file", $logs, $database);
            }
        }
    }

    /**
     * **Method** fetch last xml node from last logs file in specidied category
     *  @param string $category specidied category of logs
     * @param string $logs base name of logs type directory
     * @return null|\SimpleXMLElement last xml node
     */
    public static function fetchLastNode(string $category, string $logs) : ?\SimpleXMLElement
    {
        $directory = self::LOGS_PATH . "/$category/{$logs}s";
        $logsList = scandir($directory);

        if (empty($logsList)) {
            return null;
        }

        $lastLog = array_pop($logsList);
        $xml = self::openXMLFromFile("$directory/$lastLog");

        return $xml->{$logs}[$xml->count() - 1];
    }

    /**
     * **Method** return success opened xml file
     * @param string $logsName path to logs file
     * @return \SimpleXMLElement success opened xml file
     * @throw `\Exception`
     */
    private static function openXMLFromFile(string $logsName) : \SimpleXMLElement
    {
        $xmlFile = file_get_contents($logsName);

        if ($xmlFile === false) {
            throw new \Exception("Ошибка загрузки xml из $logsName");
        }

        return new \SimpleXMLElement($xmlFile);
    }

    /**
     * **Method** save single xml file to DB
     * @param string $file path to xml logs
     * @param string $root name of xml root node
     * @param string $database name of database
     * @return void
     */
    private static function saveXMLToDB(string $file, string $root, string $database) : void
    {
        $result = [];
        $xml = self::openXMLFromFile($file);

        foreach ($xml->$root as $item)
        {
            $buffer = [];

            foreach ($item as $tag => $value) {
                $buffer[$tag] = (string) $value;
            }

            $result[] = $buffer;
        }

        DB\DataBase::saveByTableName($database, $result);
    }

    /**
     * **Method** save single xml file with proccess logs to DB
     * @param string $file path to xml logs
     * @param string $botName name of bot which will saved to DB
     * @return void
     */
    private static function saveProccessGlobal(string $file, string $botName) : void
    {
        $xml = self::openXMLFromFile($file);

        $result = [
            'name' => $botName,
            'start' => (string) $xml->{self::XML_PROCCESS_GLOBAL_TAG}->timeStarting,
            'proccessing' => (string) $xml->{self::XML_PROCCESS_GLOBAL_TAG}->timeProccessing,
            'reading' => (string) ($xml->{self::XML_PROCCESS_GLOBAL_TAG}->messageReading ?? '0'),
            'sending' => (string) ($xml->{self::XML_PROCCESS_GLOBAL_TAG}->messageSending ?? '0'),
            'iterations' => (string) ($xml->{self::XML_PROCCESS_GLOBAL_TAG}->iterations ?? '0'),
            'averageTime' => (string) ($xml->{self::XML_PROCCESS_GLOBAL_TAG}->iterationAverageTime ?? '0'),
            'iterationMin' => (string) ($xml->{self::XML_PROCCESS_GLOBAL_TAG}->iterationMinTime ?? '0'),
            'iterationMax' => (string) ($xml->{self::XML_PROCCESS_GLOBAL_TAG}->iterationMaxTime ?? '0'),
        ];

        DB\DataBase::saveBotStatistic($result);
    }

    /**
     * **Method** init xml log file with full name (create if not exists)
     * @param string $logsBaseName base name of file with full path from logs root
     * @return string new full name of log file
     * @throw `\Exception`
     */
    private static function initialLogsXML(string $logsBaseName) : string
    {
        $logsName = self::LOGS_PATH . $logsBaseName . '_' . ((new \DateTime())->format('Y_m_d')) . '.xml';

        if (file_exists($logsName)) {
            return $logsName;
        }

        self::makeDirectory(dirname($logsName));

        $xml = new \DOMDocument('1.0', 'utf-8');
        $xml->appendChild($xml->createElement(self::XML_ROOT_TAG));

        if (! $xml->save($logsName)) {
            throw new \Exception("Ошибка сохранения xml в $logsName");
        }

        return $logsName;
    }

    /**
     * **Method** format xml file to readable view
     * @param string $logsName path to logs name
     * @return void
     * @throw `\Exception`
     */
    private static function formatLogsXML(string $logsName) : void
    {
        $xml = new \DOMDocument();
        $xml->formatOutput = true;

        if (! $xml->load($logsName, LIBXML_NOBLANKS)) {
            throw new \Exception("Ошибка загрузки xml из $logsName");
        }

        if (! $xml->save($logsName)) {
            throw new \Exception("Ошибка сохранения xml в $logsName");
        }
    }

    /**
     * **Method** save logs to initialized xml file
     * @param string $logsName path to xml file
     * @param array $logs logs data to save
     * @param string $nodeName name of log root node
     * @return void
     * @throw `\Exception`
     */
    private static function saveToXML(string $logsName, array $logs, string $nodeName) : void
    {
        $xml = self::openXMLFromFile($logsName);

        foreach ($logs as $log) {
            if (is_array($log)) {
                $xmlNode = $xml->addChild($nodeName);
                $xmlNode->addAttribute('id', uniqid('', true));

                foreach ($log as $tag => $note) {
                    $xmlNode->{$tag} = $note;
                }
            } else {
                $xmlNode = $xml->addChild($nodeName,  $log);
                $xmlNode->addAttribute('id', uniqid('', true));
            }
        }

        if ($xml->asXML($logsName) === false) {
            throw new \Exception("Ошибка сохранения xml в $logsName");
        }
    }

    /**
     * **Method** save proccess logs to initialized xml file
     * @param string $logsName path to logs file
     * @param array $globalProccess data of global proccess statistic
     * @param array $detailProccess data of proccess detail
     * @return void
     * @throw `\Exception`
     */
    private static function saveProccessToXML(string $logsName, array $globalProccess, array $detailProccess) : void
    {
        $xml = self::openXMLFromFile($logsName);

        foreach ($globalProccess as $tag => $note) {
            $xml->{self::XML_PROCCESS_GLOBAL_TAG}->{$tag} = $note;
        }

        if (! isset($xml->{self::XML_PROCCESS_DETAIL_TAG})) {
            $xml->addChild(self::XML_PROCCESS_DETAIL_TAG);
        }

        foreach ($detailProccess as $proccess) {
            $xmlDetailNode = $xml->{self::XML_PROCCESS_DETAIL_TAG}->addChild(self::XML_PROCCESS_NODE_TAG);
            $xmlDetailNode->addAttribute('id', uniqid('', true));

            foreach ($proccess as $tag => $note) {
                $xmlDetailNode->{$tag} = $note;
            }
        }

        if ($xml->asXML($logsName) === false) {
            throw new \Exception("Ошибка сохранения xml в $logsName");
        }
    }

    /**
     * **Method** recursive archive logs file from specified root
     * @param string $baseDir root from will archved files
     * @return void
     */
    private static function archiveLogsRecursive(string $baseDir) : void
    {
        foreach (scandir($baseDir) as $file) {
            $path = $baseDir . "/$file";

            if (is_dir($path) && ! in_array($file, ['.', '..'])) {
                self::archiveLogsRecursive($path);
            } elseif (is_file($path)) {
                self::archiveFile($path, self::ARCHIVE_POSTFIX);
            }
        }
    }
}
