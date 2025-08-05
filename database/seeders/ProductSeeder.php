<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'title' => 'Laptop Gaming ROG Strix',
                'description' => 'Laptop gaming de alta gama con procesador Intel Core i7, 16GB RAM, SSD 512GB y tarjeta gráfica RTX 3060. Perfecta para gaming y trabajo profesional.',
                'stock' => 15,
                'price' => 1299.99,
                'discount' => 10,
                'sku' => 'LAP001',
                'is_active' => true,
            ],
            [
                'title' => 'Smartphone Samsung Galaxy S23',
                'description' => 'Smartphone premium con pantalla AMOLED de 6.1", cámara triple de 50MP, 8GB RAM y 256GB almacenamiento. Incluye cargador inalámbrico.',
                'stock' => 25,
                'price' => 899.99,
                'discount' => 5,
                'sku' => 'PHN001',
                'is_active' => true,
            ],
            [
                'title' => 'Auriculares Sony WH-1000XM4',
                'description' => 'Auriculares inalámbricos con cancelación de ruido líder en la industria. Batería de 30 horas y sonido de alta resolución.',
                'stock' => 30,
                'price' => 349.99,
                'discount' => 15,
                'sku' => 'AUD001',
                'is_active' => true,
            ],
            [
                'title' => 'Monitor 4K Dell UltraSharp',
                'description' => 'Monitor profesional de 27" con resolución 4K, panel IPS, cobertura sRGB del 99% y conectividad USB-C. Ideal para diseño y productividad.',
                'stock' => 12,
                'price' => 599.99,
                'discount' => 0,
                'sku' => 'MON001',
                'is_active' => true,
            ],
            [
                'title' => 'Teclado Mecánico Corsair K95',
                'description' => 'Teclado mecánico gaming con switches Cherry MX, iluminación RGB personalizable y teclas macro programables.',
                'stock' => 20,
                'price' => 199.99,
                'discount' => 20,
                'sku' => 'TEC001',
                'is_active' => true,
            ],
            [
                'title' => 'Mouse Gaming Logitech G Pro X',
                'description' => 'Mouse gaming inalámbrico ultraligero con sensor HERO 25K, switches mecánicos y batería de 70 horas.',
                'stock' => 35,
                'price' => 149.99,
                'discount' => 0,
                'sku' => 'MOU001',
                'is_active' => true,
            ],
            [
                'title' => 'Tablet iPad Air 5ta Gen',
                'description' => 'Tablet con chip M1, pantalla Liquid Retina de 10.9", 64GB almacenamiento y compatibilidad con Apple Pencil.',
                'stock' => 18,
                'price' => 599.99,
                'discount' => 8,
                'sku' => 'TAB001',
                'is_active' => true,
            ],
            [
                'title' => 'Cámara Canon EOS R6',
                'description' => 'Cámara mirrorless full-frame con sensor de 20.1MP, estabilización de imagen de 5 ejes y grabación 4K.',
                'stock' => 8,
                'price' => 2499.99,
                'discount' => 5,
                'sku' => 'CAM001',
                'is_active' => true,
            ],
            [
                'title' => 'Consola PlayStation 5',
                'description' => 'Consola de videojuegos de nueva generación con SSD ultrarrápido, ray tracing y audio 3D. Incluye control DualSense.',
                'stock' => 5,
                'price' => 499.99,
                'discount' => 0,
                'sku' => 'CON001',
                'is_active' => true,
            ],
            [
                'title' => 'Smartwatch Apple Watch Series 8',
                'description' => 'Reloj inteligente con GPS, monitor de salud avanzado, pantalla Always-On y resistencia al agua.',
                'stock' => 22,
                'price' => 399.99,
                'discount' => 12,
                'sku' => 'WAT001',
                'is_active' => true,
            ],
            [
                'title' => 'Altavoz Bluetooth JBL Charge 5',
                'description' => 'Altavoz portátil resistente al agua con 20 horas de batería y función de powerbank para cargar dispositivos.',
                'stock' => 40,
                'price' => 179.99,
                'discount' => 25,
                'sku' => 'SPK001',
                'is_active' => true,
            ],
            [
                'title' => 'Disco Duro Externo 2TB',
                'description' => 'Disco duro portátil USB 3.0 con 2TB de capacidad, diseño compacto y software de respaldo automático.',
                'stock' => 50,
                'price' => 89.99,
                'discount' => 0,
                'sku' => 'HDD001',
                'is_active' => true,
            ],
            [
                'title' => 'Webcam Logitech C920',
                'description' => 'Webcam HD 1080p con micrófono estéreo, enfoque automático y corrección de luz automática.',
                'stock' => 3,
                'price' => 79.99,
                'discount' => 10,
                'sku' => 'WEB001',
                'is_active' => true,
            ],
            [
                'title' => 'Router WiFi 6 ASUS AX6000',
                'description' => 'Router de alta velocidad con WiFi 6, 8 antenas, puertos Gigabit y tecnología AiMesh.',
                'stock' => 0,
                'price' => 299.99,
                'discount' => 0,
                'sku' => 'ROU001',
                'is_active' => true,
            ],
            [
                'title' => 'Impresora HP LaserJet Pro',
                'description' => 'Impresora láser monocromática con impresión dúplex automática, WiFi y velocidad de 38 ppm.',
                'stock' => 10,
                'price' => 249.99,
                'discount' => 15,
                'sku' => 'PRI001',
                'is_active' => false,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            // Create a placeholder image for each product
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => 'products/placeholder.jpg',
                'alt_text' => $product->title,
                'sort_order' => 0,
                'is_primary' => true,
            ]);
        }
    }
}
