<?php

namespace TailgateWeb\Actions\Dashboard;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

class UploadAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $filterSubset = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
        {
            public function readCell($column, $row, $worksheetName = '') {
                if (in_array($row, [2,3,4,8,9,13,14,18,19,23,24,28,29,33,34,38,39,43,44,48,49,53,54,58,59])) {
                    if (in_array($column, ['K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD'])) {
                        return true;
                    }
                }
                return false;
            }
        };

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly('2019');
        $reader->setReadFilter($filterSubset);
        $file = $this->request->getUploadedFiles()['excel-file'];
        $path = __DIR__ ."/test.xlsx";
        $file->moveTo($path);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $lastColumn = $sheet->getHighestColumn();
        $excelData = [];
        foreach($sheet->getRowIterator() as $rowIndex => $row) {
            $array = $sheet->rangeToArray('A'.$rowIndex.':'.$lastColumn.$rowIndex);
            $array = collect($array[0])->filter(function($cell){return !is_null($cell);})->toArray();
            if (empty($array)) continue;
            $excelData[] = $array;
            // dd($rowIndex, $array);
        }
        // dd($excelData);

        $playerNames = collect($excelData[0])->values();
        array_shift($excelData);
        $scores = collect($excelData)->map(function($thing) {
            return array_values($thing);
        })->toArray();
        // dd($scores);

        $groupId = '900c9281-c325-491b-a62a-a6c242ae189e';

        // foreach($playerNames as $playerName) {

        //     // register
        //     $apiResponse = $this->apiClient->post("/register", [
        //         'email' => "{$playerName}@email.com",
        //         'password' => 'password',
        //         'confirmPassword' => 'password',
        //     ]);
        //     $data = $apiResponse->getData();
        //     $user = $data['data'];
        //     $userId = $user['userId'];

        //     // activate
        //     $apiResponse = $this->apiClient->patch("/activate/{$userId}", ['email' => "{$playerName}@email.com"]);

        //     // add member
        //     $apiResponse = $this->apiClient->post("/v1/admin/groups/{$groupId}/member", ['userId' => $userId]);
        // };

        // $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        // $data = $apiResponse->getData();
        // $group = $data['data'];
        // $members = collect($group['members'])->sortBy('email')->values();
        // unset($members[20]);
        // unset($members[21]);

        // foreach($members as $key => $member) {

        //     $memberId = $member['memberId'];
        //     $apiResponse = $this->apiClient->post("/v1/admin/groups/{$groupId}/member/{$memberId}/player", [
        //         'username' => $playerNames[$key],
        //     ]);
        // }

        $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();
        $group = $data['data'];
        $players = collect($group['players'])->sortBy('username')->values();

        // dd($players);

        $games = [
            "d54d2f04-7bb9-4d81-a258-bfbe67c7671f",
            "04224248-c529-4958-8a68-64015da0dd67",
            "84766a0c-9f0e-4f35-aa2b-0b8a29072a8c",
            "a866b3e3-c257-4ded-bd80-b029b1cc09ee",
            "648e8f49-c74c-45c5-8cd5-a3103ec79460",
            "fadecdb1-4798-473b-b6f6-a7fd791b18f8",
            "63ed20f9-a58a-4036-a5d3-fa124a6c26ef",
            "b291d737-81cf-445f-8fb7-a127e61718fa",
            "11502b3b-1965-4371-a195-a00fd58a25b1",
            "c6fbd607-a8e9-4581-8559-0c141ac07e09",
            "0b69252b-6a84-4612-850b-229adcb565ce",
            "b9493493-b8c5-47bb-85c2-d0561687d2da",
        ];

        $scoreGroups = [
            [0,1],
            [2,3],
            [4,5],
            [6,7],
            [8,9],
            [10,11],
            [12,13],
            [14,15],
            [16,17],
            [18,19],
            [20,21],
            [22,23]
        ];
set_time_limit(180);
        foreach ($players as $key => $player) {

            $playerId = $player['playerId'];

            foreach ($games as $gameKey => $game) {

                $home = 0;
                $away = 1;
                // if (in_array($gameKey, [2,5,8,9,11])) {
                //     $home = 1;
                //     $away = 0;
                // }

                $apiResponse = $this->apiClient->post("/v1/groups/{$groupId}/player/{$playerId}/score", [
                    'gameId' => $game,
                    'homeTeamPrediction' => $scores[$scoreGroups[$gameKey][$home]][$key],
                    'awayTeamPrediction' => $scores[$scoreGroups[$gameKey][$away]][$key]
                ]);
            }
        }
    }
}