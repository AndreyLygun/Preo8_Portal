<?php

namespace App\DRX;

use App\DRX;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\HttpClientException;
use Mockery\Exception;


class ApprovalStatus
{

    public $odata;

    public function __construct($odata = null)
    {
        $this->odata = $odata ?? new DRXClient();
    }

    private function GetTaskId($docId)
    {
        if (!isset($docId)) return null;
        try {
            $tasks = $this->odata->from('IDocumentFlowTasks')
                ->select('Id')
                ->whereRaw("AttachmentDetails/Any(att: att/AttachmentId eq $docId)")
                ->order('Id', 'desc')
                ->take(1)
                ->get();
            return $tasks[0]["Id"] ?? null;
        } catch (GuzzleException $ex) {
            return null;
        }
    }

    private  function GetAssignments($taskId)
    {
        // возвращает
        if (!isset($taskId)) return null;
        try {
            $asignments = $this->odata->from('IEntityApprovalAssignments')
                ->where('Task/Id', $taskId)
                ->where('Status', '!=', 'Aborted')
                ->order(array(['Id', 'asc']))
                ->expand(['Performer', 'CompletedBy', 'Texts'])
                ->get();
            return $asignments;
        } catch (GuzzleException $ex) {
            return null;
        }
    }

    private  function GetApprovalProcess($assignments)
    {
        $process = [];
        foreach ($assignments??[] as $a) {
            $blockName = $a["ThreadSubject"];
            if (!isset($process[$blockName]))
                $process[$blockName] = [];
            $process[$blockName]["BlockName"] = $blockName;
            $process[$blockName]["Status"] = $a['Status'];
            $process[$blockName]["Performers"][] = $a['Performer'];
            $process[$blockName]["Texts"] = $a["Texts"];
            $process[$blockName]["CreatedAt"] = Carbon::parse($a["Created"])->format('h:i d/m/y ');;
            $process[$blockName]["CompletedBy"] = $a["CompletedBy"];
            $process[$blockName]["CompletedAt"] = Carbon::parse($a["Completed"])->format('h:i d/m/y ');
            $process[$blockName]["Result"] = $a["Result"];
        }
        return $process;
    }

    public function Get($docId) {
        if (!isset($docId)) return null;
        $taskId = $this->GetTaskId($docId);
        $assignments = $this->GetAssignments($taskId);
        $process = $this->GetApprovalProcess($assignments);
        return $process;
    }

    public function GetXML($docId)
    {
        $this->odata->setEntityReturnType(false);
        $response = $this->odata->callAPIfunction('ServiceRequests/GetApprovalStatus', ["requestId" => $docId]);
        $state = $response["\x00SaintSystems\OData\ODataResponse\x00decodedBody"]['value'];
        $reviewStatus = str_replace(["\r\n", "{'status':", "'}"], '', $state);
        return $reviewStatus;
    }
}
