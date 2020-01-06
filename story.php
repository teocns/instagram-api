<?php

class IGScraperStory{
    private $src;
    private $is_video;
    private $has_mention;
    private $mentioned_username;
    public $id;


    public function getMediaUrl() : string {
        return (string) $this->src;
    }

    public function isVideo() : bool{
        return (bool)$this->is_video;
    }

    public function hasMention() : bool{
        return (bool)$this->has_mention;
    }

    public function getMention() : string {
        return (string) $this->mentioned_username;
    }
}