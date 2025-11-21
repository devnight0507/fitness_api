<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\NutritionPlan;
use App\Models\Meal;
use App\Models\UserAssignment;
use App\Models\Message;
use App\Models\CalendarEvent;
use App\Models\MotivationalQuote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@fitness.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $student1 = User::create([
            'name' => 'Mike Student',
            'email' => 'mike@fitness.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'admin_id' => $admin->id,
            'weight' => 75.5,
            'height' => 180.0,
            'age' => 25,
            'goal' => 'Build muscle and improve strength',
        ]);

        $student2 = User::create([
            'name' => 'Emma Johnson',
            'email' => 'emma@fitness.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'admin_id' => $admin->id,
            'weight' => 60.0,
            'height' => 165.0,
            'age' => 28,
            'goal' => 'Lose weight and tone up',
        ]);

        $student3 = User::create([
            'name' => 'Alex Brown',
            'email' => 'alex@fitness.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'admin_id' => $admin->id,
            'weight' => 82.0,
            'height' => 175.0,
            'age' => 30,
            'goal' => 'Improve endurance and cardio',
        ]);

        // Create Workouts
        $workout1 = Workout::create([
            'title' => 'Full Body Strength Training',
            'category' => 'Gym',
            'duration' => '45 min',
            'level' => 'Intermediate',
            'description' => 'Complete full body workout focusing on major muscle groups with compound movements.',
            'thumbnail_path' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48',
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout1->id,
            'name' => 'Barbell Squat',
            'sets' => 4,
            'reps' => '8-10',
            'rest' => 90,
            'notes' => 'Keep your back straight and core engaged',
            'youtube_url' => 'https://www.youtube.com/watch?v=ultWZbUMPL8',
            'order_index' => 0,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout1->id,
            'name' => 'Bench Press',
            'sets' => 4,
            'reps' => '8-10',
            'rest' => 90,
            'notes' => 'Control the descent and explode up',
            'youtube_url' => 'https://www.youtube.com/watch?v=rT7DgCr-3pg',
            'order_index' => 1,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout1->id,
            'name' => 'Deadlift',
            'sets' => 3,
            'reps' => '6-8',
            'rest' => 120,
            'notes' => 'Hinge at the hips, keep bar close to body',
            'youtube_url' => 'https://www.youtube.com/watch?v=op9kVnSso6Q',
            'order_index' => 2,
        ]);

        $workout2 = Workout::create([
            'title' => 'Home HIIT Cardio',
            'category' => 'Home',
            'duration' => '30 min',
            'level' => 'Beginner',
            'description' => 'High intensity interval training you can do at home with no equipment.',
            'thumbnail_path' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438',
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout2->id,
            'name' => 'Jumping Jacks',
            'sets' => 3,
            'reps' => '30s',
            'rest' => 15,
            'notes' => 'Full range of motion, keep a steady pace',
            'youtube_url' => 'https://www.youtube.com/watch?v=iSSAk4XCsRA',
            'order_index' => 0,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout2->id,
            'name' => 'Burpees',
            'sets' => 3,
            'reps' => '30s',
            'rest' => 15,
            'notes' => 'Jump high, chest to floor, maintain good form',
            'youtube_url' => 'https://www.youtube.com/watch?v=TU8QYVW0gDU',
            'order_index' => 1,
        ]);

        $workout3 = Workout::create([
            'title' => 'Upper Body Push Day',
            'category' => 'Gym',
            'duration' => '60 min',
            'level' => 'Advanced',
            'description' => 'Focused upper body pushing exercises for chest, shoulders, and triceps.',
            'thumbnail_path' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b',
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout3->id,
            'name' => 'Incline Dumbbell Press',
            'sets' => 4,
            'reps' => '10-12',
            'rest' => 60,
            'notes' => 'Focus on chest contraction, control the weight',
            'youtube_url' => 'https://www.youtube.com/watch?v=8iPEnn-ltC8',
            'order_index' => 0,
        ]);

        // Create Nutrition Plans
        $nutrition1 = NutritionPlan::create([
            'title' => 'Muscle Building Plan',
            'description' => 'High protein diet for muscle growth and recovery',
            'calories' => 2800,
            'protein' => 180,
            'carbs' => 300,
            'fats' => 80,
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        Meal::create([
            'nutrition_plan_id' => $nutrition1->id,
            'type' => 'Breakfast',
            'time' => '7:00 AM',
            'name' => 'Protein Oatmeal Bowl',
            'calories' => 550,
            'ingredients' => ['Oats 80g', 'Protein powder 30g', 'Banana 1', 'Almond butter 15g'],
            'instructions' => 'Cook oats with water, mix in protein powder, top with banana and almond butter.',
        ]);

        Meal::create([
            'nutrition_plan_id' => $nutrition1->id,
            'type' => 'Lunch',
            'time' => '12:30 PM',
            'name' => 'Chicken Rice Bowl',
            'calories' => 750,
            'ingredients' => ['Chicken breast 200g', 'Brown rice 150g', 'Vegetables 100g', 'Olive oil 10ml'],
            'instructions' => 'Grill chicken, cook rice, sauté vegetables.',
        ]);

        $nutrition2 = NutritionPlan::create([
            'title' => 'Weight Loss Plan',
            'description' => 'Calorie deficit diet with balanced macros',
            'calories' => 1800,
            'protein' => 120,
            'carbs' => 180,
            'fats' => 60,
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        Meal::create([
            'nutrition_plan_id' => $nutrition2->id,
            'type' => 'Breakfast',
            'time' => '7:30 AM',
            'name' => 'Greek Yogurt Parfait',
            'calories' => 350,
            'ingredients' => ['Greek yogurt 200g', 'Berries 100g', 'Granola 30g'],
            'instructions' => 'Layer yogurt with berries and granola.',
        ]);

        // Assign Workouts to Students
        UserAssignment::create([
            'user_id' => $student1->id,
            'assignable_type' => Workout::class,
            'assignable_id' => $workout1->id,
            'assigned_by' => $admin->id,
        ]);

        UserAssignment::create([
            'user_id' => $student2->id,
            'assignable_type' => Workout::class,
            'assignable_id' => $workout2->id,
            'assigned_by' => $admin->id,
        ]);

        // Assign Nutrition Plans to Students
        UserAssignment::create([
            'user_id' => $student1->id,
            'assignable_type' => NutritionPlan::class,
            'assignable_id' => $nutrition1->id,
            'assigned_by' => $admin->id,
        ]);

        UserAssignment::create([
            'user_id' => $student2->id,
            'assignable_type' => NutritionPlan::class,
            'assignable_id' => $nutrition2->id,
            'assigned_by' => $admin->id,
        ]);

        // Create Messages - Conversation with Mike
        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student1->id,
            'message' => 'Hey Mike! Welcome to your fitness journey. I\'ve assigned you the Full Body Strength Training program.',
            'read_at' => now()->subDays(3),
            'created_at' => now()->subDays(3),
        ]);

        Message::create([
            'sender_id' => $student1->id,
            'receiver_id' => $admin->id,
            'message' => 'Thank you! I\'m excited to get started.',
            'read_at' => now()->subDays(3)->addHours(1),
            'created_at' => now()->subDays(3)->addHours(1),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student1->id,
            'message' => 'Great! Remember to warm up properly before each session and focus on proper form over heavy weights.',
            'read_at' => now()->subDays(3)->addHours(2),
            'created_at' => now()->subDays(3)->addHours(2),
        ]);

        Message::create([
            'sender_id' => $student1->id,
            'receiver_id' => $admin->id,
            'message' => 'Hey! I just completed day 3. The deadlifts are really challenging!',
            'read_at' => now()->subDays(1),
            'created_at' => now()->subDays(1),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student1->id,
            'message' => 'That\'s totally normal! Deadlifts are one of the most demanding exercises. Make sure to keep your back straight and core engaged. Watch the video again if needed.',
            'read_at' => now()->subHours(18),
            'created_at' => now()->subHours(18),
        ]);

        Message::create([
            'sender_id' => $student1->id,
            'receiver_id' => $admin->id,
            'message' => 'Will do! Should I reduce the weight?',
            'read_at' => now()->subHours(17),
            'created_at' => now()->subHours(17),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student1->id,
            'message' => 'Yes, drop the weight by 20% and focus on perfecting your form for the next 2 weeks. Form is everything!',
            'read_at' => null,
            'created_at' => now()->subHours(16),
        ]);

        // Messages with Emma
        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student2->id,
            'message' => 'Hi Emma! I\'ve assigned you the Home HIIT Cardio program and the Weight Loss nutrition plan.',
            'read_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ]);

        Message::create([
            'sender_id' => $student2->id,
            'receiver_id' => $admin->id,
            'message' => 'Perfect! Can I do this program every day?',
            'read_at' => now()->subDays(2)->addHours(3),
            'created_at' => now()->subDays(2)->addHours(3),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student2->id,
            'message' => 'I recommend 4-5 times per week with rest days in between. Recovery is just as important as training!',
            'read_at' => now()->subDays(2)->addHours(4),
            'created_at' => now()->subDays(2)->addHours(4),
        ]);

        Message::create([
            'sender_id' => $student2->id,
            'receiver_id' => $admin->id,
            'message' => 'Got it! Also, the Greek Yogurt Parfait for breakfast is delicious 😊',
            'read_at' => now()->subHours(5),
            'created_at' => now()->subHours(5),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student2->id,
            'message' => 'So glad you like it! Nutrition is 70% of the journey. Keep it up!',
            'read_at' => null,
            'created_at' => now()->subHours(3),
        ]);

        // Messages with Alex
        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student3->id,
            'message' => 'Hi Alex! Let me know what your fitness goals are so I can create a customized plan for you.',
            'read_at' => now()->subHours(12),
            'created_at' => now()->subHours(12),
        ]);

        Message::create([
            'sender_id' => $student3->id,
            'receiver_id' => $admin->id,
            'message' => 'Thanks! I want to focus on cardio and endurance. Maybe some running programs?',
            'read_at' => null,
            'created_at' => now()->subHours(10),
        ]);

        // Create Calendar Events for Mike (Student 1)
        // This week
        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Full Body Strength Training',
            'type' => 'workout',
            'date' => Carbon::today()->toDateString(),
            'time' => '6:00 PM',
            'description' => 'Focus on compound movements with proper form',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Nutrition Plan Check-in',
            'type' => 'nutrition',
            'date' => Carbon::today()->toDateString(),
            'time' => '8:00 AM',
            'description' => 'Review meal prep for the week',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Upper Body Push Day',
            'type' => 'workout',
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '6:00 PM',
            'description' => 'Chest, shoulders, and triceps focus',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Rest Day - Active Recovery',
            'type' => 'rest',
            'date' => Carbon::today()->addDays(2)->toDateString(),
            'time' => '10:00 AM',
            'description' => 'Light stretching and mobility work',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Lower Body Workout',
            'type' => 'workout',
            'date' => Carbon::today()->addDays(3)->toDateString(),
            'time' => '6:00 PM',
            'description' => 'Squats, deadlifts, and leg accessories',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Body Composition Assessment',
            'type' => 'assessment',
            'date' => Carbon::today()->addDays(4)->toDateString(),
            'time' => '9:00 AM',
            'description' => 'Monthly progress check: weight, measurements, photos',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'HIIT Cardio Session',
            'type' => 'workout',
            'date' => Carbon::today()->addDays(5)->toDateString(),
            'time' => '7:00 AM',
            'description' => '30 minutes high intensity intervals',
            'created_by' => $admin->id,
        ]);

        // Next week
        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Full Body Workout',
            'type' => 'workout',
            'date' => Carbon::today()->addDays(7)->toDateString(),
            'time' => '6:00 PM',
            'description' => 'Start of new training week',
            'created_by' => $admin->id,
        ]);

        // Create Calendar Events for Emma (Student 2)
        CalendarEvent::create([
            'user_id' => $student2->id,
            'name' => 'Home HIIT Cardio',
            'type' => 'workout',
            'date' => Carbon::today()->toDateString(),
            'time' => '7:00 AM',
            'description' => 'Morning cardio session - no equipment needed',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student2->id,
            'name' => 'Meal Prep Day',
            'type' => 'nutrition',
            'date' => Carbon::today()->addDays(1)->toDateString(),
            'time' => '11:00 AM',
            'description' => 'Prepare healthy meals for the week',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student2->id,
            'name' => 'Active Rest - Yoga',
            'type' => 'rest',
            'date' => Carbon::today()->addDays(2)->toDateString(),
            'time' => '8:00 AM',
            'description' => 'Gentle yoga and stretching',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student2->id,
            'name' => 'HIIT Cardio',
            'type' => 'workout',
            'date' => Carbon::today()->addDays(3)->toDateString(),
            'time' => '7:00 AM',
            'description' => 'Burpees, jumping jacks, mountain climbers',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student2->id,
            'name' => 'Progress Check',
            'type' => 'assessment',
            'date' => Carbon::today()->addDays(6)->toDateString(),
            'time' => '10:00 AM',
            'description' => 'Weekly weigh-in and measurements',
            'created_by' => $admin->id,
        ]);

        // Create Calendar Events for Alex (Student 3)
        CalendarEvent::create([
            'user_id' => $student3->id,
            'name' => 'Initial Assessment',
            'type' => 'assessment',
            'date' => Carbon::today()->addDays(1)->toDateString(),
            'time' => '2:00 PM',
            'description' => 'Baseline fitness assessment and goal setting',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student3->id,
            'name' => 'Consultation - Training Plan',
            'type' => 'assessment',
            'date' => Carbon::today()->addDays(3)->toDateString(),
            'time' => '3:00 PM',
            'description' => 'Review and finalize customized endurance program',
            'created_by' => $admin->id,
        ]);

        // Create Motivational Quotes
        MotivationalQuote::create([
            'quote' => 'The only bad workout is the one that didn\'t happen.',
            'author' => 'Unknown',
            'is_active' => true,
        ]);

        MotivationalQuote::create([
            'quote' => 'Strength doesn\'t come from what you can do. It comes from overcoming the things you once thought you couldn\'t.',
            'author' => 'Rikki Rogers',
            'is_active' => true,
        ]);

        MotivationalQuote::create([
            'quote' => 'Your body can stand almost anything. It\'s your mind that you have to convince.',
            'author' => 'Unknown',
            'is_active' => true,
        ]);

        MotivationalQuote::create([
            'quote' => 'The pain you feel today will be the strength you feel tomorrow.',
            'author' => 'Unknown',
            'is_active' => true,
        ]);

        MotivationalQuote::create([
            'quote' => 'Fitness is not about being better than someone else. It\'s about being better than you used to be.',
            'author' => 'Khloe Kardashian',
            'is_active' => true,
        ]);
    }
}
