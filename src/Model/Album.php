<?php

namespace Asil\VkMarket\Model;

/**
 * Class Album
 * Описывает подборку товаров в API ВК
 */

class Album
{
    private $title;
    private $photoId;
    private $mainAlbum;
    private $albumId;

    /**
     * Album constructor.
     * @param string $title название подборки
     * @param string $photoId
     * @param bool $mainAlbum является ли подборка основной
     * @param int $albumId
     */
    public function __construct($title, $photoId = '', $mainAlbum = false, $albumId = 0)
    {
        $this->title = $title;
        $this->photoId = $photoId;
        $this->mainAlbum = $mainAlbum;
        $this->albumId = $albumId;
    }

    /**
     * @return string название подборки
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getAlbumId()
    {
        return $this->albumId;
    }

    /**
     * @param string название $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param string $photoId
     */
    public function setPhotoId($photoId)
    {
        $this->photoId = $photoId;
    }

    /**
     * @return int id подборки
     */
    public function getPhotoId()
    {
        return $this->photoId;
    }

    public function setMainAlbum()
    {
        $this->mainAlbum = true;
    }

    /**
     * @return bool
     */
    public function getMainAlbum()
    {
        return $this->mainAlbum;
    }


}