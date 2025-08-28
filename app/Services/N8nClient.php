<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class N8nClient
{
    public function post(array $data = [], array $files = []): array
    {
        $url = rtrim(env('N8N_WEBHOOK_URL',''), '/');
        if (!$url) return ['ok'=>false,'status'=>0,'body'=>'N8N_WEBHOOK_URL empty'];

        $req = Http::acceptJson();

        foreach ($files as $name => $uploadedFile) {
            if (!$uploadedFile) continue;
            $req = $req->attach($name, fopen($uploadedFile->getRealPath(), 'r'), $uploadedFile->getClientOriginalName());
        }

        $resp = $req->post($url, $data);
        $raw  = trim((string) $resp->body());
        $json = json_decode($raw, true);

        return [
            'ok'    => $resp->successful(),
            'status'=> $resp->status(),
            'body'  => (json_last_error() === JSON_ERROR_NONE) ? $json : $raw,
        ];
    }
}
