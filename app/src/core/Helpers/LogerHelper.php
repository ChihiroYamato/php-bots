<?php

namespace App\Anet\Helpers;

final class LogerHelper
{
    use FileSystemHelperTrait;

    private const LOGS_PATH = LOGS_PATH;
    private const LOGS_ERRORS_BASE_NAME = '/errors/error_report';
    private const LOGS_PROCCESS_BASE_NAME = '/proccess/proccess_report';
    private const LOGS_DEFAULT_BASE_NAME = '/default/report';
    private const XML_ROOT_TAG = 'Body';
    private const XML_PROCCESS_GLOBAL_TAG = 'Global';
    private const XML_PROCCESS_DETAIL_TAG = 'Detail';
    private const XML_PROCCESS_NODE_TAG = 'Proccessing';
    private const XML_ERROR_NODE_TAG = 'Error';

    public static function loggingProccess(array $globalProccess, $detailProccess) : string
    {
        $fileName = self::initialLogsXML(self::LOGS_PROCCESS_BASE_NAME);
        self::saveProccessToXML($fileName, $globalProccess, $detailProccess);
        self::formatLogsXML($fileName);

        return $fileName;
    }

    public static function logging(array $data, ?string $mode = null) : string
    {
        $fileName = self::initialLogsXML(($mode !== null) ? self::LOGS_DEFAULT_BASE_NAME : self::LOGS_ERRORS_BASE_NAME);
        self::saveToXML($fileName, $data, $mode ?? self::XML_ERROR_NODE_TAG);
        self::formatLogsXML($fileName);

        return $fileName;
    }

    private static function initialLogsXML(string $logsBaseName) : string
    {
        $logsName = self::LOGS_PATH . $logsBaseName . '_' . ((new \DateTime())->format('Y_m_d')) . '.xml';

        if (file_exists($logsName)) {
            return $logsName;
        }

        self::makeDirectory(self::LOGS_PATH);
        self::makeDirectory(preg_replace('/\/[^\/]+$/', '', $logsName));

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
        $xmlFile = file_get_contents($logsName);

        if ($xmlFile === false) {
            throw new \Exception("Ошибка загрузки xml из $logsName");
        }

        $xml = new \SimpleXMLElement($xmlFile);

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
        $xmlFile = file_get_contents($logsName);

        if ($xmlFile === false) {
            throw new \Exception("Ошибка загрузки xml из $logsName");
        }

        $xml = new \SimpleXMLElement($xmlFile);

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
