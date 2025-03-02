<?php

declare(strict_types = 1);

class BaseWatermark
{
    private float  $opacity;
    private string $color;
    private string $font;
    private int    $size;
    private float  $rotate;


    public function setOpacity($opacity): static
    {
        $this->opacity = $opacity;
        return $this;
    }

    public function getOpacity(): float
    {
        return $this->opacity;
    }

    public function getRotate(): float
    {
        return $this->rotate;
    }

    public function setRotate($rotate): static
    { // Новый метод
        $this->rotate = $rotate;
        return $this;
    }

    public function setColor($color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getFont(): string
    {
        return $this->font;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setFont($font): static
    {
        $this->font = $font;
        return $this;
    }

    public function setSize($size): static
    {
        $this->size = $size;
        return $this;
    }
}

class Watermark extends BaseWatermark
{
    private string $path;
    private float  $scale; // Новое свойство

    public function __construct($path)
    {
        $this->path  = $path;
        $this->scale = 1; // По умолчанию масштаб 1
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setScale($scale): static // Новый метод
    {
        $this->scale = $scale;
        return $this;
    }

    public function getScale(): float|int // Новый метод
    {
        return $this->scale;
    }
}

class TextWatermark extends BaseWatermark
{
    private string $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function createImage($width, $height)
    {
        // Создаем основное изображение
        $image   = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $bgColor);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Определяем цвет текста
        sscanf($this->getColor(), "#%2x%2x%2x", $r, $g, $b);
        $alpha     = (int)(127 * (1 - $this->getOpacity()));
        $textColor = imagecolorallocatealpha($image, $r, $g, $b, $alpha);

        // Путь к шрифту
        $fontFile = $this->getFont();
        $fontPath = __DIR__ . "/$fontFile.ttf";

        // Извлекаем размеры текста без вращения
        $box        = imagettfbbox($this->getSize(), 0, $fontPath, $this->text);
        $textWidth  = abs($box[2] - $box[0]);
        $textHeight = abs($box[1] - $box[7]);

        // Увеличиваем размер для учёта вращения
        $padding       = 10;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           // Добавляем небольшой отступ
        $rotatedWidth  = (int)($textWidth + $padding);
        $rotatedHeight = (int)($textHeight + $padding);

        // Создаем временное изображение для текста
        $textImage = imagecreatetruecolor($rotatedWidth,
            $rotatedHeight);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           // Создание нового изображения с заданной шириной и высотой
        imagealphablending($textImage,
            false);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    // Отключение альфа-прозрачности для нового изображения
        imagesavealpha($textImage,
            true);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     // Сохранение альфа-канала, чтобы поддерживать прозрачность
        $transparentColor = imagecolorallocatealpha($textImage,
            255,
            255,
            255,
            127);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      // Создание прозрачного цвета с использованием полной прозрачности (альфа-127)
        imagefill($textImage,
            0,
            0,
            $transparentColor);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        // Заполнение всего изображения прозрачным цветом

        // Рисуем текст на временном изображении
        imagettftext($textImage, $this->getSize(), 0, (int)($padding / 2), (int)($rotatedHeight - $padding / 2), $textColor, $fontPath, $this->text);

        // Вращаем текстовое изображение
        $angle            = -$this->getRotate();                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  // Поворачиваем против часовой стрелки
        $rotatedTextImage = imagerotate($textImage, $angle, $transparentColor);

        // Считываем размеры после поворота
        $finalRotatedWidth  = imagesx($rotatedTextImage);
        $finalRotatedHeight = imagesy($rotatedTextImage);

        // Центрируем текст на основном изображении
        $destX = (int)(($width - $finalRotatedWidth) / 2);
        $destY = (int)(($height + $finalRotatedHeight) / 2) - $finalRotatedHeight;

        // Копируем повёрнутое изображение текста на основное изображение
        imagecopy($image, $rotatedTextImage, $destX, $destY, 0, 0, $finalRotatedWidth, $finalRotatedHeight);

        // Освобождаем память
        imagedestroy($textImage);
        imagedestroy($rotatedTextImage);

        return $image;
    }
}

class Watermarker
{
    private BaseWatermark $watermark;
    private ?GdImage      $image;

    public function __construct(BaseWatermark $watermark)
    {
        $this->watermark = $watermark;
    }

    public function apply($sourcePath): static
    {
        // Загружаем основное изображение
        $this->image    = imagecreatefromjpeg($sourcePath);
        $watermarkImage = null;

        if ($this->watermark instanceof Watermark) {
            // Загружаем водяной знак
            $watermarkImage = imagecreatefrompng($this->watermark->getPath());

            // Применяем масштабирование
            if ($this->watermark->getScale() != 1) {
                $originalWidth  = imagesx($watermarkImage);
                $originalHeight = imagesy($watermarkImage);
                $scaledWidth    = (int)($originalWidth * $this->watermark->getScale());
                $scaledHeight   = (int)($originalHeight * $this->watermark->getScale());

                // Создаем новое изображение с заданными размерами
                $scaledWatermarkImage = imagecreatetruecolor($scaledWidth,
                    $scaledHeight);                                                                    // Создание пустого изображения в формате true color с заданной шириной и высотой
                imagealphablending($scaledWatermarkImage,
                    false);                                                                            // Отключение смешивания альфа-канала для следующего заполнения
                imagesavealpha($scaledWatermarkImage,
                    true);                                                                             // Включение сохранения альфа-канала в изображении
                $transparentColor = imagecolorallocatealpha($scaledWatermarkImage, 255, 255, 255, 127);// Создание прозрачного цвета (альфа = 127)
                imagefill($scaledWatermarkImage,
                    0,
                    0,
                    $transparentColor);                                                                // Заполнение всего нового изображения прозрачным цветом

                // Масштабируем водяной знак
                imagecopyresampled($scaledWatermarkImage, $watermarkImage, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $originalWidth, $originalHeight);

                // Используем масштабированный водяной знак
                $watermarkImage = $scaledWatermarkImage;
            }
        } elseif ($this->watermark instanceof TextWatermark) {
            $watermarkImage = $this->watermark->createImage(imagesx($this->image), imagesy($this->image));
        }

        // Получаем размеры изображений
        $imageWidth      = imagesx($this->image);
        $imageHeight     = imagesy($this->image);
        $watermarkWidth  = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);

        // Определяем координаты для центра
        $x = (int)(($imageWidth - $watermarkWidth) / 2);
        $y = (int)(($imageHeight - $watermarkHeight) / 2);

        // Накладываем водяной знак на изображение
        imagecopy($this->image, $watermarkImage, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight);

        // Освобождаем память
        imagedestroy($watermarkImage);

        return $this;
    }

    public function toBase64(): string
    {
        ob_start();
        imagejpeg($this->image);
        $imageData = ob_get_contents();
        ob_end_clean();

        imagedestroy($this->image);

        return 'data:image/jpeg;base64,' . base64_encode($imageData);
    }
}

// Создаём экземпляр класса Watermark с указанным путём к файлу изображения водяного знака
$watermark = new Watermark("test.png");

// Создаём экземпляр класса TextWatermark для текстового водяного знака
$textWatermark = new TextWatermark("YourName.рф");

$textWatermark
    ->setFont("Nunito-Medium") // Устанавливаем шрифт
    ->setOpacity(0.6) // Устанавливаем прозрачность текста (0 - полностью прозрачно, 1 - полностью непрозрачно)
    ->setColor("#effa17") // Устанавливаем цвет текста в формате HEX
    ->setRotate(-29.5)    // Устанавливаем угол поворота (положительные значения вращают по часовой стрелке)
    ->setSize(35); // Устанавливаем размер текста

// Создаём объект Watermarker для применения текстового водяного знака к вашему изображению
$watermarker = new Watermarker($textWatermark);

// Создаём экземпляр класса Watermark с указанным путём к файлу изображения водяного знака
$watermark2 = new Watermark("test.png");
$watermark2->setScale(0.5); // Устанавливаем масштаб

// Создаём объект Watermarker для применения второго водяного знака к вашему изображению
$watermarker2 = new Watermarker($watermark2);
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

<!-- Отображаем изображение с текстом в качестве водяного знака -->
<img src="<?= $watermarker->apply("zerkalo-ozera.jpg")->toBase64() ?>" alt="Изображение с текстом"/>

<!-- Отображаем изображение с водяным знаком -->
<img src="<?= $watermarker2->apply("image.jpg")->toBase64() ?>" alt="Изображение с водяным знаком"/>

</body>
</html>

