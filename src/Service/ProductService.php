<?php

namespace Asil\VkMarket\Service;

use Asil\VkMarket\Exception\VkException;
use Asil\VkMarket\Model\Photo;
use Asil\VkMarket\Model\Product;
use Asil\VkMarket\VkConnect;

class ProductService extends BaseService
{
    /**
     * @param Product $product
     * @param Photo $photo
     * @return int
     * @throws VkException
     */
    public function addProduct(Product $product, Photo $photo)
    {
        $photoService = new PhotoService($this->connection);

        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'owner_id' => '-' . $this->connection->getGroupId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'category_id' => $product->getCategoryId(),
            'deleted' => $product->getAvailability(),
            'main_photo_id' => $photoService->uploadMainPhoto($photo),
            'photo_ids' => $photoService->uploadAdditionalPhotos($photo),
            'v' => VkConnect::API_VERSION,
        ];
        if($product->getOldPrice() > 0.01 &&  $product->getOldPrice() > $product->getPrice()) {
            $arr['old_price'] = $product->getOldPrice();
        }

        $content = $this->connection->getRequest('market.add', $arr);
        $id = (int)$content['response']['market_item_id'];

        return $id;
    }

    /**
     * @param Product $product
     * @param Photo|null $photo
     * @return bool
     * @throws VkException
     */
    public function editProduct(Product $product, Photo $photo = null)
    {
        $photoService = new PhotoService($this->connection);

        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'owner_id' => '-' . $this->connection->getGroupId(),
            'item_id' => $product->getVkItemId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'category_id' => $product->getCategoryId(),
            'deleted' => $product->getAvailability(),
            'main_photo_id' => ((!$photo || !sizeof($photo->getMainPhotoParams())) && $product->getVkItemMainPhotoId() !== null) ?
                $product->getVkItemMainPhotoId() : $photoService->uploadMainPhoto($photo),
            'photo_ids' => ((!$photo || !sizeof($photo->getAdditionalPhotoParams())) && $product->getVkItemAdditionalPhotoIds() !== null) ?
                $product->getVkItemAdditionalPhotoIds() : $photoService->uploadAdditionalPhotos($photo),
            'v' => VkConnect::API_VERSION,
        ];

        if($product->getOldPrice() > 0.01 && $product->getOldPrice() > $product->getPrice()) {
            $arr['old_price'] = $product->getOldPrice();
        }
        $content = $this->connection->getRequest('market.edit', $arr);

        return true;
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteProduct($id)
    {
        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'owner_id' => '-' . $this->connection->getGroupId(),
            'item_id' => $id,
            'v' => VkConnect::API_VERSION,
        ];

        $content = $this->connection->getRequest('market.delete', $arr);

        return (boolean)$content['response'];
    }

    public function restoreProduct($id)
    {
        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'owner_id' => '-' . $this->connection->getGroupId(),
            'item_id' => $id,
            'v' => VkConnect::API_VERSION,
        ];

        $content = $this->connection->getRequest('market.restore', $arr);

        return (boolean)$content['response'];
    }

    public function getProductById($id)
    {
        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'item_ids' => '-' . $this->connection->getGroupId() . '_' . $id,
            'owner_id' => '-' . $this->connection->getGroupId(),
            'extended' => 1,
            'v' => VkConnect::API_VERSION,
        ];

        $content = $this->connection->getRequest('market.getById', $arr);

        $product = false;
        if (sizeof($content['response']['items'])) {
            if ($content['response']['items'][0]['id'] !== 0) {
                $product = new Product(
                    $content['response']['items'][0]['title'],
                    $content['response']['items'][0]['sku'],
                    $content['response']['items'][0]['description'],
                    $content['response']['items'][0]['category']['id'],
                    $content['response']['items'][0]['price']['amount'],
                    $content['response']['items'][0]['availability']);
                $product->setVkItemId($content['response']['items'][0]['id']);
                $product->setAlbum($content['response']['items'][0]['albums_ids']);
                if (sizeof($content['response']['items'][0]['photos'])) {
                    foreach ($content['response']['items'][0]['photos'] as $key => $vkPhoto) {
                        // если фото идёт первой в массиве, то это главное фото товара,
                        // иначе фото является дополнительным
                        if ($key === 0) {
                            $product->setVkItemMainPhotoId($vkPhoto['id']);
                        } else {
                            $product->setVkItemAdditionalPhotoIds($vkPhoto['id']);
                        }
                    }
                }

                // доп. поля товара
                if (isset($vkItem['likes']['user_likes'])) {
                    $product->setVkItemUserlikes($vkItem['likes']['user_likes']);
                }

                if (isset($vkItem['views_count'])) {
                    $product->setVkItemViewsCount($vkItem['views_count']);
                }
            }
        }
        return $product;
    }

    public function getProductsInAlbum($albumId, $count = 10, $offset = 0)
    {
        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'owner_id' => '-' . $this->connection->getGroupId(),
            'album_id' => $albumId,
            'count' => $count,
            'offset' => $offset,
            'extended' => 1,
            'v' => VkConnect::API_VERSION,
        ];

        $content = $this->connection->getRequest('market.get', $arr);

        $productsArr = [];

        if (sizeof($content['response']['items'])) {
            foreach ($content['response']['items'] as $item) {
                if ($item['id'] !== 0) {
                    $product = new Product($item['title'],
                        $item['description'], $item['category']['id'],
                        $item['price']['amount'], $item['availability']);

                    $product->setVkItemId($item['id']);

                    if (sizeof($item['photos'])) {
                        foreach ($item['photos'] as $key => $vkPhoto) {
                            // если фото идёт первой в массиве, то это главное фото товара,
                            // иначе фото является дополнительным
                            if ($key === 0) {
                                $product->setVkItemMainPhotoId($vkPhoto['id']);
                            } else {
                                $product->setVkItemAdditionalPhotoIds($vkPhoto['id']);
                            }
                        }
                    }

                    // доп. поля товара
                    if (isset($vkItem['likes']['user_likes'])) {
                        $product->setVkItemUserlikes($vkItem['likes']['user_likes']);
                    }

                    if (isset($vkItem['views_count'])) {
                        $product->setVkItemViewsCount($vkItem['views_count']);
                    }

                    $productsArr[] = $product;
                }
            }
        }

        return $productsArr;
    }

    public function getCategories($count = 100, $offset = '')
    {
        $arr = [
            'access_token' => $this->connection->getAccessToken(),
            'count' => $count,
            'offset' => $offset,
            'v' => VkConnect::API_VERSION,
        ];

        $content = $this->connection->getRequest('market.getCategories', $arr);
        return $content;
    }

}