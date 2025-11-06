<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStream
{
    private $path = "";
    private $stream = null;
    private $buffer = 102400;
    private $start = -1;
    private $end = -1;
    private $size = 0;

    public function __construct($filePath)
    {
        $this->path = $filePath;
    }

    /**
     * Open stream
     */
    private function open()
    {
        if (!($this->stream = fopen($this->path, 'rb'))) {
            throw new \Exception('Could not open stream for reading');
        }
    }

    /**
     * Set proper headers and send the file
     */
    public function start()
    {
        if (!file_exists($this->path)) {
            abort(404, 'Video file not found');
        }

        $this->size = filesize($this->path);
        $this->start = 0;
        $this->end = $this->size - 1;

        $this->open();

        return new StreamedResponse(function () {
            $this->stream();
        }, 200, $this->getHeaders());
    }

    /**
     * Stream video with range request support
     */
    private function stream()
    {
        $i = $this->start;
        set_time_limit(0);

        while (!feof($this->stream) && $i <= $this->end) {
            $bytesToRead = $this->buffer;

            if (($i + $bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }

            $data = fread($this->stream, $bytesToRead);
            echo $data;
            flush();

            $i += $bytesToRead;
        }

        fclose($this->stream);
    }

    /**
     * Set proper header to serve the video content
     */
    private function getHeaders()
    {
        $headers = [
            'Content-Type' => $this->getMimeType(),
            'Content-Length' => $this->size,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ];

        // Check for range request
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $this->start;
            $c_end = $this->end;

            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                return response()->json(['error' => 'Multiple ranges not supported'], 416)
                    ->header('Content-Range', "bytes $this->start-$this->end/$this->size");
            }

            if ($range == '-') {
                $c_start = $this->size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }

            $c_end = ($c_end > $this->end) ? $this->end : $c_end;

            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
                return response()->json(['error' => 'Invalid range'], 416)
                    ->header('Content-Range', "bytes $this->start-$this->end/$this->size");
            }

            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;

            fseek($this->stream, $this->start);

            $headers['Content-Length'] = $length;
            $headers['Content-Range'] = "bytes $this->start-$this->end/$this->size";
            $headers['HTTP/1.1 206 Partial Content'] = '';
        }

        return $headers;
    }

    /**
     * Get MIME type of the file
     */
    private function getMimeType()
    {
        $mimeType = mime_content_type($this->path);

        if ($mimeType === false) {
            $ext = pathinfo($this->path, PATHINFO_EXTENSION);

            $mimeTypes = [
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                'avi' => 'video/x-msvideo',
                'mov' => 'video/quicktime',
                'flv' => 'video/x-flv',
            ];

            return $mimeTypes[$ext] ?? 'application/octet-stream';
        }

        return $mimeType;
    }
}
