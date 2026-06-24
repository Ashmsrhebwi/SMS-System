<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use App\Models\Tag;
use App\Models\TemplateCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@fera.clinic'],
            [
                'name'      => 'Admin User',
                'password'  => 'password',
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        // Standard user (for testing role-based access)
        User::firstOrCreate(
            ['email' => 'staff@fera.clinic'],
            [
                'name'      => 'Staff User',
                'password'  => 'password',
                'role'      => 'standard',
                'is_active' => true,
            ]
        );

        // Default tags
        $tagData = [
            ['name' => 'VIP',            'color' => '#6366f1'],
            ['name' => 'Implant',        'color' => '#8b5cf6'],
            ['name' => 'Whitening',      'color' => '#06b6d4'],
            ['name' => 'Follow-Up',      'color' => '#f97316'],
            ['name' => 'Cancelled',      'color' => '#ef4444'],
            ['name' => 'Potential Lead', 'color' => '#eab308'],
        ];

        foreach ($tagData as $tag) {
            Tag::firstOrCreate(['name' => $tag['name']], ['color' => $tag['color']]);
        }

        // Template categories
        $categoryNames = ['Offers', 'Appointments', 'Reminders', 'Implants', 'Whitening', 'Follow Up', 'Promotions'];
        $categoryMap = [];
        foreach ($categoryNames as $catName) {
            $cat = TemplateCategory::firstOrCreate(['name' => $catName]);
            $categoryMap[$catName] = $cat->id;
        }

        // Example SMS templates
        $templates = [
            ['name' => 'Appointment Reminder', 'category' => 'Appointments',
             'content' => "Hi {name}, this is FeRa Clinic. Friendly reminder about your upcoming appointment. Need to reschedule? Visit {tracking_url} or call us."],
            ['name' => 'Dental Offer', 'category' => 'Offers',
             'content' => "Hi {name}, FeRa Clinic has an exclusive offer this month. Book your check-up and receive a complimentary polish. Details: {tracking_url}"],
            ['name' => 'Whitening Offer', 'category' => 'Whitening',
             'content' => "Hi {name}, brighten your smile with FeRa Clinic's teeth whitening — now at a special price. Book today: {tracking_url}"],
            ['name' => 'Implant Consultation', 'category' => 'Implants',
             'content' => "Hi {name}, restore your smile with dental implants at FeRa Clinic. Schedule a free consultation: {tracking_url}"],
            ['name' => 'Follow-up Reminder', 'category' => 'Follow Up',
             'content' => "Hi {name}, FeRa Clinic following up on your recent treatment. Any concerns? Book a check-up: {tracking_url}"],
            ['name' => 'Monthly Promotion', 'category' => 'Promotions',
             'content' => "Hi {name}, FeRa Clinic has a special offer just for you this month! Claim it here: {tracking_url}"],
        ];

        foreach ($templates as $tpl) {
            SmsTemplate::firstOrCreate(
                ['name' => $tpl['name']],
                [
                    'content'     => $tpl['content'],
                    'created_by'  => $admin->id,
                    'category_id' => $categoryMap[$tpl['category']] ?? null,
                ]
            );
        }
    }
}
