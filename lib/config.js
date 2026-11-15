// Central place for shop details. Edit these to match the real business.

export const SHOP = {
  name: 'THE FLOWER SHOP',
  tagline: 'Fresh flowers, delivered with love across Nepalgunj',
  city: 'Nepalgunj',
  address: 'Tribhuvan Chowk, Nepalgunj, Banke, Nepal', // TODO: replace with real address
  phone: '+977-98XXXXXXXX', // TODO: replace
  whatsapp: '97798XXXXXXXX', // digits only, with country code. TODO: replace
  email: 'hello@theflowershop.np', // TODO: replace
  hours: 'Open daily, 8:00 AM – 7:00 PM',
};

export const DELIVERY = {
  area: 'Nepalgunj (Banke) and nearby areas',
  fee: 100, // NPR, flat and minimal
  freeAbove: 5000, // free delivery for orders at or above this subtotal
  sameDayCutoffHour: 15, // orders placed before 3 PM Nepal time can be delivered the same day
  maxDaysAhead: 30,
  slots: ['9:00 AM – 12:00 PM', '12:00 PM – 3:00 PM', '3:00 PM – 6:00 PM'],
};

export const FLOWER_CATEGORIES = [
  'Boutique Bouquets',
  'Roses',
  'Carnations',
  'Lilies',
  'Gerberas',
  'Sunflowers',
  'Mixed Bouquets',
];

export const ADDON_CATEGORIES = ['Cakes', 'Chocolates', 'Teddy Bears', 'Greeting Cards'];

export const OCCASIONS = [
  { key: 'birthday', label: 'Birthday', blurb: 'Brighten their special day' },
  { key: 'anniversary', label: 'Anniversary', blurb: 'Celebrate years of togetherness' },
  { key: 'valentine', label: "Valentine's & Love", blurb: 'Say it with roses' },
  { key: 'wedding', label: 'Wedding', blurb: 'Elegant blooms for the big day' },
  { key: 'congratulations', label: 'Congratulations', blurb: 'Cheer every milestone' },
  { key: 'condolence', label: 'Condolence', blurb: 'Gentle, respectful tributes' },
];

export const ORDER_STATUSES = [
  'pending',
  'confirmed',
  'preparing',
  'out_for_delivery',
  'delivered',
  'cancelled',
];

export const STATUS_LABELS = {
  pending: 'Pending',
  confirmed: 'Confirmed',
  preparing: 'Preparing',
  out_for_delivery: 'Out for delivery',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
};
