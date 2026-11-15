<?php
declare(strict_types=1);

const SHOP = [
    'name' => 'THE FLOWER FOUNDRY',
    'tagline' => 'Fresh flowers, delivered with love across Nepalgunj',
    'city' => 'Nepalgunj',
    'address' => 'Tribhuvan Chowk, Nepalgunj, Banke, Nepal',
    'phone' => '+977 9847962276',
    'whatsapp' => '9779847962276',
    'hours' => 'Open daily, 8:00 AM – 7:00 PM',
];

const DELIVERY = [
    'area' => 'Nepalgunj (Banke) and nearby areas',
    'fee' => 100,
    'free_above' => 5000,
    'same_day_cutoff_hour' => 15,
    'max_days_ahead' => 30,
    'slots' => ['9:00 AM – 12:00 PM', '12:00 PM – 3:00 PM', '3:00 PM – 6:00 PM'],
];

const FLOWER_CATEGORIES = [
    'Boutique Bouquets', 'Roses', 'Carnations', 'Lilies', 'Gerberas', 'Sunflowers', 'Mixed Bouquets',
];
const ADDON_CATEGORIES = ['Cakes', 'Chocolates', 'Teddy Bears', 'Greeting Cards'];
const OCCASIONS = [
    'birthday' => ['label' => 'Birthday', 'blurb' => 'Brighten their special day'],
    'anniversary' => ['label' => 'Anniversary', 'blurb' => 'Celebrate years of togetherness'],
    'valentine' => ['label' => "Valentine's & Love", 'blurb' => 'Say it with roses'],
    'wedding' => ['label' => 'Wedding', 'blurb' => 'Elegant blooms for the big day'],
    'congratulations' => ['label' => 'Congratulations', 'blurb' => 'Cheer every milestone'],
    'condolence' => ['label' => 'Condolence', 'blurb' => 'Gentle, respectful tributes'],
];
const ORDER_STATUSES = ['pending', 'shipped', 'delivered', 'confirmed', 'preparing', 'out_for_delivery', 'cancelled'];
const ORDER_STATUS_OPTIONS = ['pending', 'shipped', 'delivered'];
const STATUS_LABELS = [
    'pending' => 'Pending',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'out_for_delivery' => 'Out for delivery',
    'cancelled' => 'Cancelled',
];

function seed_products(): array
{
    return [
        ['name' => 'Blush & Crimson Boutique', 'category' => 'Boutique Bouquets', 'price' => 3450, 'image' => '/images/blush-crimson-boutique.png', 'featured' => 1, 'occasions' => 'valentine,anniversary,birthday', 'description' => 'A luxurious boutique bouquet of crimson and pink roses with cream accents, baby’s breath and soft eucalyptus, hand-wrapped in blush and white premium paper with a satin ribbon.', 'kind' => 'flower'],
        ['name' => 'Peach Garden Boutique', 'category' => 'Boutique Bouquets', 'price' => 2850, 'image' => '/images/peach-garden-boutique.png', 'featured' => 1, 'occasions' => 'birthday,congratulations,anniversary', 'description' => 'Pink roses and carnations with delicate baby’s breath, daisies and wild filler, wrapped in dreamy peach floral paper and tied with a generous ribbon.', 'kind' => 'flower'],
        ['name' => 'Classic Red Rose Bouquet', 'category' => 'Roses', 'price' => 1850, 'image' => '/images/classic-red-rose-bouquet.png', 'featured' => 1, 'occasions' => 'valentine,anniversary', 'description' => 'Twelve fresh red roses with green filler in elegant paper. A timeless way to say I love you.', 'kind' => 'flower'],
        ['name' => 'Romantic 30 Red Roses', 'category' => 'Roses', 'price' => 4250, 'image' => '/images/romantic-30-red-roses.png', 'featured' => 0, 'occasions' => 'valentine,anniversary,wedding', 'description' => 'Thirty long red roses in a statement bouquet for the most unforgettable moments.', 'kind' => 'flower'],
        ['name' => 'Pink Carnation Bouquet', 'category' => 'Carnations', 'price' => 1350, 'image' => '/images/pink-carnation-bouquet.png', 'featured' => 1, 'occasions' => 'birthday,congratulations', 'description' => 'Soft pink carnations with greenery. Long-lasting, sweet and full of gratitude.', 'kind' => 'flower'],
        ['name' => 'Sunshine Sunflower Bouquet', 'category' => 'Sunflowers', 'price' => 1650, 'image' => '/images/sunshine-sunflower-bouquet.png', 'featured' => 0, 'occasions' => 'birthday,congratulations', 'description' => 'Cheerful sunflowers that bring warmth and positivity to any day.', 'kind' => 'flower'],
        ['name' => 'White Lily Elegance', 'category' => 'Lilies', 'price' => 2650, 'image' => '/images/white-lily-elegance.png', 'featured' => 0, 'occasions' => 'wedding,condolence,congratulations', 'description' => 'Graceful white lilies with a gentle fragrance. A refined choice for weddings and sympathy.', 'kind' => 'flower'],
        ['name' => 'Yellow Gerbera Joy', 'category' => 'Gerberas', 'price' => 1250, 'image' => '/images/yellow-gerbera-joy.png', 'featured' => 0, 'occasions' => 'birthday,congratulations', 'description' => 'Bright yellow gerberas wrapped in cream paper to spread instant happiness.', 'kind' => 'flower'],
        ['name' => 'Mixed Seasonal Bouquet', 'category' => 'Mixed Bouquets', 'price' => 2250, 'image' => '/images/mixed-seasonal-bouquet.png', 'featured' => 1, 'occasions' => 'birthday,anniversary,congratulations', 'description' => 'A colourful mix of the freshest seasonal blooms, arranged by our florists.', 'kind' => 'flower'],
        ['name' => 'Rose & Carnation Gift Box', 'category' => 'Boutique Bouquets', 'price' => 3250, 'image' => '/images/rose-carnation-gift-box.png', 'featured' => 0, 'occasions' => 'valentine,birthday,anniversary', 'description' => 'Roses and carnations arranged in a keepsake round box. Ready to gift, no vase needed.', 'kind' => 'flower'],
        ['name' => 'Chocolate Truffle Cake (500 g)', 'category' => 'Cakes', 'price' => 1200, 'image' => '/images/addon-cake-chocolate.png', 'featured' => 0, 'occasions' => '', 'description' => 'Rich chocolate truffle cake, freshly baked.', 'kind' => 'addon'],
        ['name' => 'Vanilla Berry Cake (500 g)', 'category' => 'Cakes', 'price' => 1100, 'image' => '/images/addon-cake-vanilla.png', 'featured' => 0, 'occasions' => '', 'description' => 'Light vanilla sponge with berry cream.', 'kind' => 'addon'],
        ['name' => 'Assorted Chocolate Box', 'category' => 'Chocolates', 'price' => 650, 'image' => '/images/addon-chocolate-box.png', 'featured' => 0, 'occasions' => '', 'description' => 'A box of assorted premium chocolates.', 'kind' => 'addon'],
        ['name' => 'Hazelnut Chocolate Pralines', 'category' => 'Chocolates', 'price' => 900, 'image' => '/images/addon-chocolate-hazelnut.png', 'featured' => 0, 'occasions' => '', 'description' => 'Hazelnut chocolate pralines in a gift pack.', 'kind' => 'addon'],
        ['name' => 'Cuddly Teddy Bear (30 cm)', 'category' => 'Teddy Bears', 'price' => 800, 'image' => '/images/addon-teddy-small.svg', 'featured' => 0, 'occasions' => '', 'description' => 'Soft and huggable teddy, 30 cm tall.', 'kind' => 'addon'],
        ['name' => 'Big Teddy Bear (60 cm)', 'category' => 'Teddy Bears', 'price' => 1800, 'image' => '/images/addon-teddy-large.svg', 'featured' => 0, 'occasions' => '', 'description' => 'Extra-large teddy bear, 60 cm tall.', 'kind' => 'addon'],
        ['name' => 'Handwritten Greeting Card', 'category' => 'Greeting Cards', 'price' => 150, 'image' => '/images/addon-card-greeting.svg', 'featured' => 0, 'occasions' => '', 'description' => 'We handwrite your message on a premium card.', 'kind' => 'addon'],
        ['name' => 'Premium Pop-up Love Card', 'category' => 'Greeting Cards', 'price' => 300, 'image' => '/images/addon-card-popup.svg', 'featured' => 0, 'occasions' => '', 'description' => 'A 3D pop-up heart card with your message.', 'kind' => 'addon'],
    ];
}
