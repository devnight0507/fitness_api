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
            'video_path' => 'videos/workouts/full_body_strength.mp4',
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
            'order_index' => 0,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout1->id,
            'name' => 'Bench Press',
            'sets' => 4,
            'reps' => '8-10',
            'rest' => 90,
            'order_index' => 1,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout1->id,
            'name' => 'Deadlift',
            'sets' => 3,
            'reps' => '6-8',
            'rest' => 120,
            'order_index' => 2,
        ]);

        $workout2 = Workout::create([
            'title' => 'Home HIIT Cardio',
            'category' => 'Home',
            'duration' => '30 min',
            'level' => 'Beginner',
            'description' => 'High intensity interval training you can do at home with no equipment.',
            'video_path' => 'videos/workouts/hiit_cardio.mp4',
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
            'order_index' => 0,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout2->id,
            'name' => 'Burpees',
            'sets' => 3,
            'reps' => '30s',
            'rest' => 15,
            'order_index' => 1,
        ]);

        $workout3 = Workout::create([
            'title' => 'Upper Body Push Day',
            'category' => 'Gym',
            'duration' => '60 min',
            'level' => 'Advanced',
            'description' => 'Focused upper body pushing exercises for chest, shoulders, and triceps.',
            'video_path' => 'videos/workouts/upper_body_push.mp4',
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

        // Create Messages
        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student1->id,
            'message' => 'Hey Mike! How are you finding the new workout plan?',
            'read_at' => now()->subHours(2),
        ]);

        Message::create([
            'sender_id' => $student1->id,
            'receiver_id' => $admin->id,
            'message' => 'Hi! It\'s great, but the deadlifts are tough!',
            'read_at' => now()->subHour(),
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $student1->id,
            'message' => 'That\'s normal! Make sure to focus on form first. Let me know if you need any adjustments.',
            'read_at' => null,
        ]);

        // Create Calendar Events
        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Upper Body Workout',
            'type' => 'workout',
            'date' => Carbon::today()->toDateString(),
            'time' => '6:00 PM',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Leg Day',
            'type' => 'workout',
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '6:00 PM',
            'created_by' => $admin->id,
        ]);

        CalendarEvent::create([
            'user_id' => $student1->id,
            'name' => 'Rest Day',
            'type' => 'rest',
            'date' => Carbon::today()->addDays(2)->toDateString(),
            'time' => 'All Day',
            'created_by' => $student1->id,
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
