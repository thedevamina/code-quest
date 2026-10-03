<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['slug' => 'oop', 'title' => 'Object-Oriented Programming', 'code' => 'CS-106', 'level' => 'Intermediate', 'description' => 'Learn object-oriented principles through classes, inheritance, polymorphism, pointers, streams, and exceptions.'],
            ['slug' => 'dsa', 'title' => 'Data Structures & Algorithms', 'code' => 'CS-204', 'level' => 'Intermediate', 'description' => 'Design efficient programs with lists, stacks, queues, trees, graphs, heaps, hashing, and algorithm analysis.'],
            ['slug' => 'dbms', 'title' => 'Database Management Systems', 'code' => 'CS-302', 'level' => 'Foundations', 'description' => 'Model, query, and protect the data systems that power modern products.'],
        ];
        $chapterTitles = [
            'oop' => ['The Big Picture', 'Structure & Classes', 'Objects & Classes', 'Constructors & Class State', 'Functions & Overloading', 'Composition & Aggregation', 'Inheritance', 'Multiple Inheritance', 'Operator Overloading', 'Pointers', 'Virtual Functions', 'Polymorphism & Friends', 'Streams & Files', 'Error Handling & File I/O', 'Multiple Programs', 'Templates & Exceptions'],
            'dsa' => ['Foundations & Arrays', 'Linked Lists', 'Doubly Linked Lists', 'Stacks & Queues', 'Queues & Deques', 'Recursion', 'Trees & Binary Trees', 'BSTs & AVL Trees', 'Graphs', 'Heaps', 'Hashing', 'Algorithms & Complexity', 'Sorting I', 'Sorting II', 'Divide & Conquer', 'Greedy Algorithms'],
            'dbms' => ['Database Foundations', 'Relational Models', 'SQL Essentials', 'Normalization', 'Transactions & Security'],
        ];
        $lessonContent = json_decode(file_get_contents(__DIR__.'/lesson_content.json'), true, 512, JSON_THROW_ON_ERROR);
        $courseQuestions = [
            'oop' => [
                ['Which principle protects an object\'s internal state?', ['Inheritance', 'Encapsulation', 'Polymorphism', 'Composition'], 1],
                ['What is the primary purpose of a constructor?', ['Destroy objects', 'Initialize a valid object', 'Copy an object', 'Overload operators'], 1],
                ['Which access specifier limits members to the class?', ['public', 'protected', 'private', 'friend'], 2],
                ['What does a destructor do?', ['Creates objects', 'Cleans up resources', 'Initializes members', 'Copies objects'], 1],
                ['Which feature lets a derived class reuse a base class?', ['Encapsulation', 'Inheritance', 'Polymorphism', 'Abstraction'], 1],
                ['What is function overloading?', ['Redefining a base method', 'Same name with different parameters', 'Calling another class', 'Returning different types'], 1],
                ['Which keyword declares a generic class or function?', ['virtual', 'template', 'typedef', 'namespace'], 1],
                ['What is a copy constructor?', ['A no-argument constructor', 'A constructor copying another object', 'A destructor', 'A value-returning method'], 1],
                ['What does virtual enable?', ['Static binding', 'Late binding', 'Object copying', 'Memory allocation'], 1],
                ['Which operator accesses members through an object pointer?', ['.', '->', '::', ':'], 1],
            ],
            'dsa' => [
                ['What is array index access complexity?', ['O(n)', 'O(1)', 'O(log n)', 'O(n log n)'], 1],
                ['What does a singly linked node store?', ['Only data', 'Data and next pointer', 'Two pointers', 'An index'], 1],
                ['Which structure is LIFO?', ['Queue', 'Stack', 'List', 'Tree'], 1],
                ['Which structure is FIFO?', ['Stack', 'Queue', 'Tree', 'Heap'], 1],
                ['What is the height of a balanced binary tree?', ['O(n)', 'O(log n)', 'O(n log n)', 'O(1)'], 1],
                ['Which traversal is left, root, right?', ['Pre-order', 'In-order', 'Post-order', 'Level-order'], 1],
                ['Worst-case insertion in a BST is?', ['O(1)', 'O(log n)', 'O(n)', 'O(n log n)'], 2],
                ['Which sort uses divide and conquer?', ['Bubble sort', 'Insertion sort', 'Merge sort', 'Selection sort'], 2],
                ['What computes a hash-table index?', ['Comparison', 'Hash function', 'Sort', 'Tree'], 1],
                ['What commonly implements a priority queue?', ['List', 'Array', 'Heap', 'Stack'], 2],
            ],
            'dbms' => [
                ['What does a primary key identify?', ['A column', 'A row', 'A database', 'A query'], 1],
                ['Which SQL keyword retrieves rows?', ['GET', 'SELECT', 'FETCH', 'RETRIEVE'], 1],
                ['What does normalization reduce?', ['Query speed', 'Redundancy', 'Table count', 'Index size'], 1],
                ['Which join returns only matching rows?', ['LEFT', 'RIGHT', 'INNER', 'FULL'], 2],
                ['What does ACID include?', ['Atomicity, Consistency, Isolation, Durability', 'Accuracy, Completeness, Integrity, Data', 'Access, Control, Identity, Data', 'Atomic, Concurrent, Indexed, Distributed'], 0],
                ['Which command removes all rows but keeps the table?', ['DROP', 'DELETE', 'TRUNCATE', 'REMOVE'], 2],
                ['What is a foreign key?', ['A key referencing another table', 'A unique index', 'A join', 'A sorting keyword'], 0],
                ['Which normal form removes partial dependencies?', ['1NF', '2NF', '3NF', 'BCNF'], 1],
                ['What does GROUP BY do?', ['Sorts', 'Groups rows for aggregation', 'Joins', 'Filters'], 1],
                ['Which index enforces uniqueness?', ['Clustered', 'Non-clustered', 'Unique', 'Full-text'], 2],
            ],
        ];
        foreach ($courses as $courseData) {
            DB::table('courses')->updateOrInsert(['slug' => $courseData['slug']], $courseData + ['created_at' => now(), 'updated_at' => now()]);
            $courseId = DB::table('courses')->where('slug', $courseData['slug'])->value('id');
            foreach ($chapterTitles[$courseData['slug']] as $number => $title) {
                $chapterDescription = 'Chapter '.str_pad((string) ($number + 1), 2, '0', STR_PAD_LEFT).' explores '.$title.' in the '.$courseData['title'].' outline.';
                DB::table('chapters')->updateOrInsert(['course_id' => $courseId, 'number' => $number + 1], ['slug' => $courseData['slug'].'-'.($number + 1), 'title' => $title, 'description' => $chapterDescription, 'status' => $number < 3 ? ($number < 2 ? 'completed' : 'in-progress') : ($number === 3 ? 'available' : 'locked'), 'duration' => '2 lectures', 'created_at' => now(), 'updated_at' => now()]);
                $chapterId = DB::table('chapters')->where(['course_id' => $courseId, 'number' => $number + 1])->value('id');
                $lesson = $lessonContent[$courseData['slug'].'-'.($number + 1)];
                DB::table('learning_materials')->updateOrInsert(['chapter_id' => $chapterId], ['title' => $lesson['title'], 'content' => json_encode($lesson, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
                $testId = DB::table('tests')->where(['course_id' => $courseId, 'chapter_id' => $chapterId, 'type' => 'chapter'])->value('id');
                if (! $testId) {
                    $testId = DB::table('tests')->insertGetId(['course_id' => $courseId, 'chapter_id' => $chapterId, 'type' => 'chapter', 'title' => 'Chapter '.($number + 1).' test', 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('questions')->where('test_id', $testId)->delete();
                $questions = $number === 2 ? $courseQuestions[$courseData['slug']] : [['Which topic is the focus of this chapter?', [$title, 'An unrelated topic', 'No topic', 'All topics equally'], 0]];
                foreach ($questions as $sort => [$questionText, $options, $correct]) {
                    $questionId = DB::table('questions')->insertGetId(['test_id' => $testId, 'question' => $questionText, 'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now()]);
                    foreach ($options as $optionIndex => $option) {
                        DB::table('question_options')->insert(['question_id' => $questionId, 'option' => $option, 'is_correct' => $optionIndex === $correct, 'sort_order' => $optionIndex, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
            foreach (['midterm', 'final'] as $type) {
                DB::table('exams')->updateOrInsert(['course_id' => $courseId, 'type' => $type], ['title' => ucfirst($type).' examination', 'created_at' => now(), 'updated_at' => now()]);
                $examId = DB::table('exams')->where(['course_id' => $courseId, 'type' => $type])->value('id');
                DB::table('exam_questions')->where('exam_id', $examId)->delete();
                $examQuestions = array_slice($courseQuestions[$courseData['slug']], 0, $type === 'midterm' ? 6 : 10);
                foreach ($examQuestions as $sort => [$questionText, $options, $correct]) {
                    DB::table('exam_questions')->insert(['exam_id' => $examId, 'question' => $questionText, 'options' => json_encode($options), 'correct_index' => $correct, 'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
        if (! DB::table('users')->where('email', 'admin@codequest.local')->exists()) {
            DB::table('users')->insert(['name' => 'Code Quest Admin', 'email' => 'admin@codequest.local', 'password' => Hash::make('password'), 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
