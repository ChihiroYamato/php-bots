<?php

namespace App\Anet\Helpers;

use App\Anet\DB;

final class LogerHelper
{
    use FileSystemHelperTrait;

    private const LOGS_PATH = LOGS_PATH;
    private const LOGS_PROCCESS_BASE_NAME = '/proccess/proccess_report';
    private const RUNTIME_LOGS_NAME = '/runtime/logs';
    private const XML_ROOT_TAG = 'body';
    private const XML_PROCCESS_GLOBAL_TAG = 'global';
    private const XML_PROCCESS_DETAIL_TAG = 'detail';
    private const XML_PROCCESS_NODE_TAG = 'proccessing';
    private const ARCHIVE_POSTFIX = '-old';

    public static function archiveLogs(string $baseDir = self::LOGS_PATH) : void
    {
        foreach (scandir($baseDir) as $file) {
            $path = $baseDir . "/$file";

            if (is_dir($path) && ! in_array($file, ['.', '..', trim(dirname(self::RUNTIME_LOGS_NAME), '/')])) {
                self::archiveLogs($path);
            } elseif (is_file($path) && $file !== '.gitkeep') {
                self::archiveFile($path, self::ARCHIVE_POSTFIX);
            }
        }
    }

    public static function print(string $category, string $message) : void
    {
        $logsFile = self::LOGS_PATH . "/$category" . self::RUNTIME_LOGS_NAME;
        self::makeDirectory(dirname($logsFile));
        file_put_contents($logsFile, ((new \DateTime())->format('Y-m-d H:i:s')) . " -- $message\n", FILE_APPEND);
    }

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

    public static function saveToDB(string $category, string $logs, string $database) : void
    {
        $directory = self::LOGS_PATH . "/$category/{$logs}s";

        foreach (scandir($directory) as $file) {
            if (is_file("$directory/$file") && mb_strpos($file, self::ARCHIVE_POSTFIX) === false) {
                self::saveXMLToDB("$directory/$file", $logs, $database);
            }
        }
    }

    public static function saveProccessToDB(string $category) : void
    {
        $directory = dirname(self::LOGS_PATH . "/$category" . self::LOGS_PROCCESS_BASE_NAME);

        foreach (scandir($directory) as $file) {
            if (is_file("$directory/$file") && mb_strpos($file, self::ARCHIVE_POSTFIX) === false) {
                self::saveProccessGlobal("$directory/$file", $category);
            }
        }
    }

    private static function openXMLFromFile(string $logsName) : \SimpleXMLElement
    {
        $xmlFile = file_get_contents($logsName);

        if ($xmlFile === false) {
            throw new \Exception("Ошибка загрузки xml из $logsName");
        }

        return new \SimpleXMLElement($xmlFile);
    }

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

    private static function saveToXML(string $logsName, array $errors, string $nodeName) : void
    {
        $xml = self::openXMLFromFile($logsName);

        foreach ($errors as $error) {
            $xmlNode = $xml->addChild($nodeName);
            $xmlNode->addAttribute('id', uniqid('', true));

            foreach ($error as $tag => $note) {
                $xmlNode->{$tag} = $note;
            }
        }

        if ($xml->asXML($logsName) === false) {
            throw new \Exception("Ошибка сохранения xml в $logsName");
        }
    }

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
}
