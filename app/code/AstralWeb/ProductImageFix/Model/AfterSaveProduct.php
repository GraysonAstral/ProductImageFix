<?php

namespace AstralWeb\ProductImageFix\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;

/**
 * Class AfterSaveProduct
 * @package AstralWeb\ProductImageFix\Model
 */
class AfterSaveProduct
{
    const ATTRIBUTE_TABLE = 'eav_attribute';

    const PRODUCT_VARCHAR_TABLE = 'catalog_product_entity_varchar';

    const ENTITY_TYPE_TABLE = 'eav_entity_type';

    const PRODUCT_ENTITY = 'catalog_product';

    const NO_SELECTION = 'no_selection';

    /* @var array */
    private $imageAttributes =
        [
            'image',
            'small_image',
            'thumbnail',
            'swatch_image'
        ];

    /* @var Mysql */
    private $connect;

    /**
     * AfterSaveProduct constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->connect = $resourceConnection->getConnection();
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function afterSave(Product $product)
    {
        $attributesIds = $this->getAttributeIds();

        if (!count($attributesIds)) {
            return $this;
        }

        $this->connect->delete(
            self::PRODUCT_VARCHAR_TABLE,
            [
                'attribute_id in (?)' => $attributesIds,
                'value = ?' => self::NO_SELECTION,
                'entity_id = ?' => $product->getEntityId()
            ]
        );

        return $this;
    }

    /**
     * @return string
     */
    private function getProductEntity(): string
    {
        $sql = $this->connect
            ->select()
            ->from(
                self::ENTITY_TYPE_TABLE,
                [
                    'entity_type_id',
                ]
            )
            ->where('entity_type_code = ?', self::PRODUCT_ENTITY);

        $query = $this->connect->fetchRow($sql);

        return $query['entity_type_id'] ?? '';
    }

    /**
     * @return array
     */
    private function getAttributeIds(): array
    {
        $entityTypeId = $this->getProductEntity();

        if (!$entityTypeId) {
            return [];
        }

        $sql = $this->connect
            ->select()
            ->from(
                self::ATTRIBUTE_TABLE,
                [
                    'attribute_id',
                ]
            )
            ->where('entity_type_id = ?', $entityTypeId)
            ->where('attribute_code IN (?)', $this->imageAttributes);

        $query = $this->connect->fetchAll($sql);

        return array_column($query, 'attribute_id');
    }
}
