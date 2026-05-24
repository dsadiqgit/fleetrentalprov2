<?php
/**
 * Blog Posts Data
 * This acts as a lightweight database for the blog.
 */

$posts = [
    [
        'title' => 'Why Your Car Rental Business Needs to Ditch Paper for Automated E-Signatures',
        'slug' => 'automated-esignature.php',
        'date' => 'March 31, 2026',
        'timestamp' => 1774915200,
        'category' => 'Strategy',
        'author' => 'Fleet Rental Pro Team',
        'image' => 'https://images.unsplash.com/photo-1565514020179-026b92b84bb6?auto=format&fit=crop&q=80&w=800',
        'excerpt' => 'If you’re still handing people a physical pen and a stack of coffee-stained paper, you’re basically running a business from 1995.',
        'author_image' => 'https://ui-avatars.com/api/?name=Fleet+Rental&background=f9fafb&color=111827'
    ],
    [
        'title' => 'Car-as-a-Service (CaaS): Doubling Revenue via Subscriptions',
        'slug' => 'caas-uk-rental-revenue.php',
        'date' => 'March 27, 2026',
        'timestamp' => 1774569600,
        'category' => 'Strategy',
        'author' => 'Kilian Cahill',
        'image' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=1200',
        'excerpt' => 'Vehicle subscriptions are expected to double by 2030. Learn how UK rental companies are pivoting toward recurring revenue.',
        'author_image' => 'https://ui-avatars.com/api/?name=Kilian+C&background=f9fafb&color=111827'
    ],
    [
        'title' => 'Fleet secures $20M Series A',
        'slug' => '#',
        'date' => 'December 15, 2025',
        'timestamp' => 1734220800,
        'category' => 'Product',
        'author' => 'Riya Grover',
        'image' => 'https://images.unsplash.com/photo-1542362567-b054cd1321c1?q=80&w=600',
        'excerpt' => 'to build AI agents that automate revenue operations for thousands of rental fleets.',
        'author_image' => 'https://ui-avatars.com/api/?name=Riya+G&background=f9fafb&color=111827'
    ],
    [
        'title' => 'Introducing Sequence 2.0',
        'slug' => '#',
        'date' => 'October 2, 2025',
        'timestamp' => 1727827200,
        'category' => 'The Sequence experience',
        'author' => 'Riya Grover',
        'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600',
        'excerpt' => 'Today, we\'re launching Sequence 2.0: The first AI-native revenue automation platform built for fleet owners.',
        'author_image' => 'https://ui-avatars.com/api/?name=Riya+G&background=f9fafb&color=111827'
    ],
    [
        'title' => '10 Reasons Why Companies Move from Stripe Billing to Sequence',
        'slug' => '#',
        'date' => 'July 14, 2025',
        'timestamp' => 1720915200,
        'category' => 'State of the market',
        'author' => 'Enda Cahill',
        'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=600',
        'excerpt' => 'Over 50% of Sequence\'s revenue now comes from Stripe Billing migrations. Here are the most common.',
        'author_image' => 'https://ui-avatars.com/api/?name=Enda+C&background=f9fafb&color=111827'
    ],
    [
        'title' => 'Introducing Sequence Automations: The Operational Layer for Revenue Teams',
        'slug' => '#',
        'date' => 'March 24, 2029',
        'timestamp' => 1868918400,
        'category' => 'Product',
        'author' => 'Kilian Cahill',
        'image' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?q=80&w=400',
        'excerpt' => 'The first AI-native revenue automation platform built for fleet owners.',
        'author_image' => 'https://ui-avatars.com/api/?name=Kilian+C&background=f9fafb&color=111827'
    ],
    [
        'title' => 'E-Invoice Compliance for B2B Companies: 2028 Guide',
        'slug' => '#',
        'date' => 'March 18, 2028',
        'timestamp' => 1836864000,
        'category' => 'Product',
        'author' => 'Donal McKeon',
        'image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=400',
        'excerpt' => 'How to navigate the complex world of global E-invoicing.',
        'author_image' => 'https://ui-avatars.com/api/?name=Donal+M&background=f9fafb&color=111827'
    ],
     [
        'title' => 'Building Investor-Grade Finance from Day One',
        'slug' => '#',
        'date' => 'February 23, 2028',
        'timestamp' => 1834876800,
        'category' => 'State of the market',
        'author' => 'Donal McKeon',
        'image' => 'https://images.unsplash.com/photo-1553729459-efe14ef6055d?q=80&w=400',
        'excerpt' => 'How Shellix Advisory is changing the game for startups.',
        'author_image' => 'https://ui-avatars.com/api/?name=Donal+M&background=f9fafb&color=111827'
    ]
];

// Sort posts by date (newest first)
usort($posts, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});
