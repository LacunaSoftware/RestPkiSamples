<?php

namespace Lacuna;

class ChunkedUploadUtility {

    /** @var RestPkiClient */
    protected $restPkiClient;
    protected $filePath;
    protected $content;
    protected $length;

    public function __construct($restPkiClient)
    {
        $this->restPkiClient = $restPkiClient;
    }

    public function ChunkedFileUpload($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("O arquivo {$filePath} não pode ser lido.");
        }
        $this->length = filesize($filePath);
        $this->filePath = $filePath;

        return $this->UploadFile();
    }

    public function ChunkedContentUpload($content) {
        $length = strlen($content);
        if ($length <= 0) {
            throw new Exception("Não é possível enviar um conteúdo vazio.");
        }
        $this->length = $length;
        $this->content = $content;

        return $this->UploadContent();
    }

    protected function UploadFile() {
        $granted = $this->restPkiClient->get('Api/Upload');
        $chunkCount = $this->getChunkCount($this->length, $granted->chunkSize);

        $handle = fopen($this->filePath, 'rb');
        $chunk = 0;
        while (!feof($handle)) {
            $buffer = fread($handle, $granted->chunkSize);

            $this->restPkiClient->postMultipartFormData(
                "Api/Upload/{$granted->blobToken}/",
                $this->getMultiPartFormData($buffer, $granted->ticket, $chunkCount, $chunk++)
            );

            ob_flush();
            flush();
        }

        fclose($handle);
        return $granted->blobToken;
    }

    protected function UploadContent() {
        $granted = $this->restPkiClient->get('Api/Upload');
        $chunkCount = $this->getChunkCount($this->length, $granted->chunkSize);

        for ($chunk = 0; $chunk < $chunkCount; $chunk++) {
            $chunkSize = (int)min($this->length - $chunk * $granted->chunkSize, $granted->chunkSize);
            $buffer = substr($this->content, $chunk * $chunkSize, $chunkSize);

            $this->restPkiClient->postMultipartFormData(
                "Api/Upload/{$granted->blobToken}/",
                $this->getMultiPartFormData($buffer, $granted->ticket, $chunkCount, $chunk)
            );
        }

        return $granted->blobToken;
    }

    private function getChunkCount($length, $chunkSize) {
        $r = $length % $chunkSize;
        $chunkCount = (int)($length / $chunkSize) +  (($r > 0) ? 1 : 0 );
        return $chunkCount;
    }

    // gets the multipart form data array required by guzzle to perform such kind of operation
    private function getMultiPartFormData($content, $ticket, $chunks, $chunk) {
        return
            [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $content,
                        'filename' => 'binary_stream'
                    ],
                    [
                        'name' => 'ticket',
                        'contents' => $ticket
                    ],
                    [
                        'name' => 'chunks',
                        'contents' => "{$chunks}"
                    ],
                    [
                        'name' => 'chunk',
                        'contents' => "{$chunk}"
                    ]
                ]
            ];
    }


}