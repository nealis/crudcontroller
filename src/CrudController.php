<?php

namespace Nealis\CrudController;

use Nealis\EntityRepository\Data\Filter\Rule;
use Nealis\EntityRepository\Entity\EntityRepository;
use Nealis\Params\Params;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CrudController
{
    protected $defaultFilterRuleOperator = Rule::BEGINSWITH;

    /**
     * @param EntityRepository $repository
     * @param Request $request
     * @return Params
     */
    public function getQueryParams(EntityRepository $repository, Request $request)
    {
        $repository->setDefaultFilterRuleOperator($this->defaultFilterRuleOperator);
        $repository->setAllowEmptyFilters(false);
        $requestParams = $this->getRequestParams($request);
        return $repository->getQueryParams($requestParams);
    }

    /**
     * @param Request $request
     * @return Params
     */
    protected function getRequestParams(Request $request)
    {
        return Params::getInstanceFromRequest($request);
    }

    /**
     * @param EntityRepository $repository
     * @param Params $params
     * @return array
     * @deprecated
     */
    public function executeRead($repository, $params)
    {
        return $repository->readAll($params);
    }

    /**
     * @param EntityRepository $repository
     * @param Params $params
     * @return int
     * @deprecated
     */
    public function executeCount($repository, $params)
    {
        return $repository->readCount($params);
    }

    /**
     * @param EntityRepository $repository
     * @param Request $request
     * @return array
     */
    public function read($repository, Request $request)
    {
        $params = $this->getQueryParams($repository, $request);
        return $repository->readAll($params);
    }

    /**
     * @param EntityRepository $repository
     * @param Request $request
     * @return int
     */
    public function count($repository, Request $request)
    {
        $params = $this->getQueryParams($repository, $request);
        return $repository->readCount($params);
    }

    /**
     * @return string
     */
    public function getDefaultFilterRuleOperator()
    {
        return $this->defaultFilterRuleOperator;
    }

    /**
     * @param EntityRepository$repository
     */
    public function initDefaultFilterRuleOperator($repository)
    {
        $repository->setDefaultFilterRuleOperator($this->defaultFilterRuleOperator);
    }

    /**
     * @param string $defaultFilterRuleOperator
     */
    public function setDefaultFilterRuleOperator($defaultFilterRuleOperator)
    {
        $this->defaultFilterRuleOperator = $defaultFilterRuleOperator;
    }

    /**
     * @param EntityRepository $repository
     * @param array $parameters
     * @param array $filters
     * @param array $sorters
     * @param string $fileName
     * @param array $exportFields
     * @return BinaryFileResponse
     * @throws \Exception
     */
    public function exportExcelRepository($repository, $parameters = [], $filters = [], $sorters = [], $fileName = '', $exportFields = [])
    {
        if (empty($exportFields)) {

            try {
                $entity = $repository->getEntityInstance();
                $exportFields = $entity->getFieldsConfig();
            } catch (\Exception $e) {
                $exportFields = array();
            }

        }

        $this->initDefaultFilterRuleOperator($repository);
        $readQuery = $repository->getReadQuery();
        $query = $repository->prepareQuery($readQuery, $parameters, $filters, $sorters);

        return $this->exportExcel($query, $fileName, $exportFields);
    }

//    /**
//     * @param QueryBuilder $query
//     * @param string $fileName
//     * @param array $config
//     * @return BinaryFileResponse
//     */
//    public function exportExcel($query = null, $fileName = '', $config = array())
//    {
//        /** @var LazyDbExporter $exporter */
//        $exporter = $this->getAppService('export.document.db');
//        $file = $exporter->exportQuery($query, $fileName, $config);
//
//        return $this->downloadFile($file);
//    }

//    /**
//     * @param string $reportURI
//     * @param string $type
//     * @param array $parameters
//     * @param string $reportName
//     * @return mixed
//     */
//    public function report($reportURI, $type = 'pdf', $parameters = array(), $reportName = null)
//    {
//        /** @var JasperServer $report */
//        $report = $this->getAppService('jasperserver.report');
//        if ($reportName !== null) $report->setFileName($reportName);
//        $report->setType($type);
//        $report->setReportURI($reportURI);
//
//        return $report->execute($parameters);
//    }

//    /**
//     * @param $reportURI
//     * @param string $type
//     * @param array $parameters
//     * @param null $reportName
//     * @return string|BinaryFileResponse
//     */
//    public function downloadReport($reportURI, $type = 'pdf', $parameters = array(), $reportName = null)
//    {
//        $baseReportUri = $this->getAppService('jasperserver.baseUri');
//
//        try {
//            $file = $this->report($baseReportUri.$reportURI, $type, $parameters, $reportName);
//            return $this->downloadFile($file);
//        } catch (\Exception $e) {
//            return $this->getTwig()->render('@Views/index/error.twig', [
//                'title' => 'Error',
//                'message' => 'Unable to get report: probably server did not respond.'
//            ]);
//        }
//    }

//    /**
//     * @param $reportName
//     * @param string $type
//     * @param array $parameters
//     * @param $outputFileName
//     * @param string $locale
//     * @param string $resourceDir
//     * @return BinaryFileResponse
//     * @throws \Exception
//     */
//    public function downloadJasperPHPReport($reportName, $type = 'pdf', $parameters = [], $outputFileName = null, $locale = null, $resourceDir = null) {
//        /** @var JasperPHP $jasperPHP */
//        $jasperPHP = $this->app['jasperphp.jasper'];
//        if(empty($outputFileName)) $outputFileName = uniqid();
//        $result = $jasperPHP->generateReport($reportName, $outputFileName, $type, $parameters, $locale, $resourceDir);
//        if(!$result->isSuccess()) throw new \Exception($result->getErrorsString());
//        return $this->downloadFile($result->getData()['path']);
//    }

    /**
     * @param string $filePath
     * @return BinaryFileResponse
     */
    public function downloadFile($filePath)
    {
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );

        return $response;
    }

//    public function sendWebSocketMessage($body, $notifyChannels)
//    {
//        connect(
//            'ws://' . $this->app['websocket.server.address'] . ':' . $this->app['websocket.server.port'],
//            [],
//            [],
//            null,
//            $this->app['dns.ip']
//        )->then(function ($conn) use($body, $notifyChannels) {
//            /** @var WebSocket $conn */
//            $communicationMessage = [
//                'type' => 'communication',
//                'body' => json_encode($body),
//                'notifyChannels' => $notifyChannels
//            ];
//
//            $conn->send(json_encode($communicationMessage));
//            $conn->close(200);
//        }, function(\Exception $e) {
//        });
//    }
}
