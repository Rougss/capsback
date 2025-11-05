<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------
        // Créer ou mettre à jour l'utilisateur principal
        // -----------------------------
        $user = User::updateOrCreate(
            ['email' => 'coumba@capsbeauty.com'], // critère unique
            [
                'name' => 'Comba Tine',
                'password' => Hash::make('password123'),
                'salon_name' => 'Salon Caps Beauty',
                'phone' => '+221 776543210',
                'address' => 'Plateau, Dakar, Sénégal',
                'role' => 'professional',
            ]
        );

        // -----------------------------
        // Créer ou mettre à jour les services
        // -----------------------------
        $services = [
            ['name' => 'Coupe & Brushing', 'price' => 10000.00, 'duration' => 60],
            ['name' => 'Coloration', 'price' => 8500.00, 'duration' => 120],
            ['name' => 'Manucure', 'price' => 3000.00, 'duration' => 45],
            ['name' => 'Soin Visage', 'price' => 6500.00, 'duration' => 90],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $service['name'],
                ],
                [
                    'description' => 'Service de qualité professionnelle',
                    'price' => $service['price'],
                    'duration' => $service['duration'],
                ]
            );
        }

        // -----------------------------
        // Créer ou mettre à jour les clients
        // -----------------------------
        $clients = [
            ['name' => 'Marie Niang', 'phone' => '+221 77 883 99 00', 'email' => 'marie@email.com'],
            ['name' => 'Binta Boum', 'phone' => '+221 77 991 00 11', 'email' => 'binta@email.com'],
            ['name' => 'Claire Bernard', 'phone' => '+221 77 123 45 67', 'email' => 'claire@email.com'],
            ['name' => 'Emma Rousseau', 'phone' => '+221 77 234 56 78', 'email' => 'emma@email.com'],
        ];

        foreach ($clients as $clientData) {
            Client::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'email' => $clientData['email'],
                ],
                [
                    'name' => $clientData['name'],
                    'phone' => $clientData['phone'],
                    'notes' => 'Cliente régulière',
                ]
            );
        }

        // -----------------------------
        // Créer ou mettre à jour les produits
        // -----------------------------
        $products = [
            ['name' => 'Shampoing Professionnel', 'price' => 25.00, 'stock' => 20, 'category' => 'Cheveux'],
            ['name' => 'Masque Réparateur', 'price' => 35.00, 'stock' => 15, 'category' => 'Cheveux'],
            ['name' => 'Vernis à Ongles', 'price' => 12.00, 'stock' => 30, 'category' => 'Ongles'],
            ['name' => 'Crème Hydratante', 'price' => 45.00, 'stock' => 10, 'category' => 'Visage'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $product['name'],
                ],
                [
                    'description' => 'Produit de qualité premium',
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'category' => $product['category'],
                ]
            );
        }

        // -----------------------------
        // Créer des rendez-vous aléatoires
        // -----------------------------
        $allClients = Client::where('user_id', $user->id)->get();
        $allServices = Service::where('user_id', $user->id)->get();

        for ($i = 0; $i < 5; $i++) {
            Appointment::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'client_id' => $allClients->random()->id,
                    'service_id' => $allServices->random()->id,
                    'appointment_date' => now()->addDays(rand(1, 30)),
                ],
                [
                    'status' => 'confirmed',
                    'notes' => 'Rendez-vous confirmé par téléphone',
                ]
            );
        }
    }
}
