# 修正使用程式碼新增圖片至 Product Gallery ，無法取得正確 Attribute 問題

<br>

### 適用版本：
* Magento 2 : 2.2.1

<br>

### 新增圖片至 Gallery 程式碼

```php

 	/* @var $product \Magento\Catalog\Model\Product */
     $product
     	->addImageToMediaGallery(
     		$filePath,
     		[
            	"image",
            	"small_image",
            	"thumbnail",
     		],
     		true,
     		false
     	);

```
<br>

### 無法對應屬性
![image Logo](/image/image.png)

<br>

### 官方 Issue 參考
* [Github](https://github.com/magento/magento2/issues/6803)
