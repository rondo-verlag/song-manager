<?php

namespace helpers;

class Youtube
{
    function extractVideoId($url)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        $query = [];

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }

        // Long format: youtube.com/watch?v=VIDEO_ID
        if (strpos($host, 'youtube.com') !== false && isset($query['v'])) {
            return $query['v'];
        }

        // Short format: youtu.be/VIDEO_ID
        if ($host === 'youtu.be') {
            return trim($path, '/');
        }

        // Embed format: youtube.com/embed/VIDEO_ID
        if (strpos($path, '/embed/') !== false) {
            return basename($path);
        }

        return null;
    }

    function checkLink($videoId, $apiKey)
    {
        $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=status&id={$videoId}&key={$apiKey}";
        $response = file_get_contents($apiUrl);

        if ($response === FALSE) {
            return ['status' => 'error', 'message' => 'Failed to connect to YouTube API'];
        }

        $data = json_decode($response, true);

        if (empty($data['items'])) {
            return ['status' => 'unavailable', 'message' => 'Video not found or removed'];
        }

        $status = $data['items'][0]['status'];

        if ($status['privacyStatus'] === 'private') {
            return ['status' => 'private', 'message' => 'Video is private'];
        } elseif ($status['privacyStatus'] === 'public') {
            return ['status' => 'available', 'message' => 'Video is public'];
        } else {
            return ['status' => 'unlisted', 'message' => 'Video is unlisted or limited'];
        }
    }
}