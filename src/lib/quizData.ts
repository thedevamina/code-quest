export type Question = {
  id: number;
  question: string;
  options: string[];
  correctIndex: number;
};

export type Quiz = {
  questions: Question[];
  passingScore: number;
};

const oopQuiz: Quiz = {
  passingScore: 60,
  questions: [
    { id: 1, question: 'Which principle keeps an object\'s internal state protected from direct outside access?', options: ['Inheritance', 'Encapsulation', 'Polymorphism', 'Composition'], correctIndex: 1 },
    { id: 2, question: 'What is the primary purpose of a constructor?', options: ['To destroy objects', 'To initialize an object in a valid state', 'To copy one object to another', 'To overload operators'], correctIndex: 1 },
    { id: 3, question: 'Which access specifier makes members accessible only within the same class?', options: ['public', 'protected', 'private', 'friend'], correctIndex: 2 },
    { id: 4, question: 'What does a destructor do?', options: ['Creates a new object', 'Cleans up resources when an object is destroyed', 'Initializes member variables', 'Copies an object'], correctIndex: 1 },
    { id: 5, question: 'Which feature allows a derived class to reuse members of a base class?', options: ['Encapsulation', 'Inheritance', 'Polymorphism', 'Abstraction'], correctIndex: 1 },
    { id: 6, question: 'What is function overloading?', options: ['Redefining a function in a derived class', 'Having multiple functions with the same name but different parameters', 'Calling a function from another class', 'Returning different types from a function'], correctIndex: 1 },
    { id: 7, question: 'Which keyword is used to create a class template that works with any data type?', options: ['virtual', 'template', 'typedef', 'namespace'], correctIndex: 1 },
    { id: 8, question: 'What is a copy constructor?', options: ['A constructor that takes no arguments', 'A constructor that creates an object by copying another object', 'A constructor that deletes an object', 'A constructor that returns a value'], correctIndex: 1 },
    { id: 9, question: 'What does the virtual keyword enable?', options: ['Static binding', 'Dynamic (late) binding', 'Object copying', 'Memory allocation'], correctIndex: 1 },
    { id: 10, question: 'Which operator is used to access members through a pointer to an object?', options: ['.', '->', '::', ':'], correctIndex: 1 },
  ],
};

const dsaQuiz: Quiz = {
  passingScore: 60,
  questions: [
    { id: 1, question: 'What is the time complexity of accessing an element in an array by index?', options: ['O(n)', 'O(1)', 'O(log n)', 'O(n log n)'], correctIndex: 1 },
    { id: 2, question: 'In a singly linked list, what does each node store?', options: ['Only data', 'Data and a pointer to the next node', 'Two pointers', 'An index'], correctIndex: 1 },
    { id: 3, question: 'Which data structure follows Last-In-First-Out (LIFO) order?', options: ['Queue', 'Stack', 'Linked List', 'Tree'], correctIndex: 1 },
    { id: 4, question: 'Which data structure follows First-In-First-Out (FIFO) order?', options: ['Stack', 'Queue', 'Tree', 'Heap'], correctIndex: 1 },
    { id: 5, question: 'What is the height of a balanced binary tree with n nodes?', options: ['O(n)', 'O(log n)', 'O(n log n)', 'O(1)'], correctIndex: 1 },
    { id: 6, question: 'Which traversal visits the left subtree, root, then right subtree?', options: ['Pre-order', 'In-order', 'Post-order', 'Level-order'], correctIndex: 1 },
    { id: 7, question: 'What is the worst-case time complexity of insertion into a BST?', options: ['O(1)', 'O(log n)', 'O(n)', 'O(n log n)'], correctIndex: 2 },
    { id: 8, question: 'Which algorithm uses divide-and-conquer to sort in O(n log n)?', options: ['Bubble sort', 'Insertion sort', 'Merge sort', 'Selection sort'], correctIndex: 2 },
    { id: 9, question: 'What does a hash table use to compute the index for a key?', options: ['A comparison function', 'A hash function', 'A sorting algorithm', 'A tree structure'], correctIndex: 1 },
    { id: 10, question: 'Which structure is typically used to implement a priority queue?', options: ['Linked list', 'Array', 'Heap', 'Stack'], correctIndex: 2 },
  ],
};

const dbmsQuiz: Quiz = {
  passingScore: 60,
  questions: [
    { id: 1, question: 'What does a primary key uniquely identify?', options: ['A column', 'A row in a table', 'A database', 'A query'], correctIndex: 1 },
    { id: 2, question: 'Which SQL keyword is used to retrieve data from a database?', options: ['GET', 'SELECT', 'FETCH', 'RETRIEVE'], correctIndex: 1 },
    { id: 3, question: 'What does normalization primarily reduce?', options: ['Query speed', 'Data redundancy', 'Table count', 'Index size'], correctIndex: 1 },
    { id: 4, question: 'Which join returns only matching rows from both tables?', options: ['LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 'FULL OUTER JOIN'], correctIndex: 2 },
    { id: 5, question: 'What does ACID stand for in database transactions?', options: ['Atomicity, Consistency, Isolation, Durability', 'Accuracy, Completeness, Integrity, Data', 'Access, Control, Identity, Data', 'Atomic, Concurrent, Indexed, Distributed'], correctIndex: 0 },
    { id: 6, question: 'Which command removes all rows from a table but keeps the structure?', options: ['DROP', 'DELETE', 'TRUNCATE', 'REMOVE'], correctIndex: 2 },
    { id: 7, question: 'What is a foreign key?', options: ['A primary key in another table', 'A unique index', 'A type of join', 'A SQL keyword for sorting'], correctIndex: 0 },
    { id: 8, question: 'Which normal form eliminates partial dependencies on a composite primary key?', options: ['1NF', '2NF', '3NF', 'BCNF'], correctIndex: 1 },
    { id: 9, question: 'What does the GROUP BY clause do?', options: ['Sorts results', 'Groups rows with the same values for aggregation', 'Joins tables', 'Filters rows'], correctIndex: 1 },
    { id: 10, question: 'Which type of index enforces uniqueness on a column?', options: ['Clustered index', 'Non-clustered index', 'Unique index', 'Full-text index'], correctIndex: 2 },
  ],
};

export const quizzes: Record<string, Quiz> = {
  oop: oopQuiz,
  dsa: dsaQuiz,
  dbms: dbmsQuiz,
};

const examQuestion = (id: number, question: string, options: string[], correctIndex: number): Question => ({ id, question, options, correctIndex });

export const examQuizzes: Record<string, { midterm: Quiz; final: Quiz }> = {
  oop: {
    midterm: { passingScore: 60, questions: [...oopQuiz.questions, examQuestion(11, 'Which relationship is best represented by composition?', ['A car owning its engine', 'A student attending a course', 'A user viewing a page', 'A report referencing a date'], 0), examQuestion(12, 'Why should a polymorphic base class usually have a virtual destructor?', ['To enable overloading', 'To ensure derived cleanup runs', 'To make members static', 'To avoid constructors'], 1)] },
    final: { passingScore: 60, questions: [...oopQuiz.questions, examQuestion(11, 'Which relationship is best represented by composition?', ['A car owning its engine', 'A student attending a course', 'A user viewing a page', 'A report referencing a date'], 0), examQuestion(12, 'Why should a polymorphic base class usually have a virtual destructor?', ['To enable overloading', 'To ensure derived cleanup runs', 'To make members static', 'To avoid constructors'], 1), examQuestion(13, 'What does a template provide?', ['One implementation for compatible types', 'A runtime database', 'Automatic garbage collection', 'A replacement for inheritance'], 0), examQuestion(14, 'Which stream type is intended primarily for file output?', ['ifstream', 'ofstream', 'stringstream', 'cin'], 1), examQuestion(15, 'What is the safest general cleanup strategy in modern C++?', ['Manual global deletes', 'RAII and ownership types', 'Ignoring destructors', 'Casting every pointer'], 1)] },
  },
  dsa: {
    midterm: { passingScore: 60, questions: [...dsaQuiz.questions, examQuestion(11, 'Which structure is most appropriate for breadth-first traversal?', ['Stack', 'Queue', 'Heap', 'Hash table'], 1), examQuestion(12, 'What is the average lookup complexity of a well-sized hash table?', ['O(n^2)', 'O(log n)', 'O(1)', 'O(V + E)'], 2)] },
    final: { passingScore: 60, questions: [...dsaQuiz.questions, examQuestion(11, 'Which structure is most appropriate for breadth-first traversal?', ['Stack', 'Queue', 'Heap', 'Hash table'], 1), examQuestion(12, 'What is the average lookup complexity of a well-sized hash table?', ['O(n^2)', 'O(log n)', 'O(1)', 'O(V + E)'], 2), examQuestion(13, 'What is the main risk of a badly unbalanced BST?', ['It uses no memory', 'Operations can degrade to O(n)', 'It cannot store integers', 'It always becomes a heap'], 1), examQuestion(14, 'Which sort combines sorted subarrays?', ['Merge sort', 'Selection sort', 'Bubble sort', 'Insertion sort'], 0), examQuestion(15, 'What must a recursive algorithm include?', ['Only a loop', 'A base case and progress toward it', 'A hash function', 'A database connection'], 1)] },
  },
  dbms: {
    midterm: { passingScore: 60, questions: [...dbmsQuiz.questions, examQuestion(11, 'Which clause filters groups after aggregation?', ['WHERE', 'HAVING', 'ORDER BY', 'VALUES'], 1), examQuestion(12, 'What does a foreign key protect?', ['Referential integrity', 'CPU speed', 'Password length', 'Sort stability'], 0)] },
    final: { passingScore: 60, questions: [...dbmsQuiz.questions, examQuestion(11, 'Which clause filters groups after aggregation?', ['WHERE', 'HAVING', 'ORDER BY', 'VALUES'], 1), examQuestion(12, 'What does a foreign key protect?', ['Referential integrity', 'CPU speed', 'Password length', 'Sort stability'], 0), examQuestion(13, 'Which ACID property makes a transaction all-or-nothing?', ['Atomicity', 'Consistency', 'Isolation', 'Durability'], 0), examQuestion(14, 'Which normal form removes transitive dependencies?', ['1NF', '2NF', '3NF', '4NF'], 2), examQuestion(15, 'What is a good defense against SQL injection?', ['String concatenation', 'Parameterized queries', 'More indexes', 'A larger table'], 1)] },
  },
};
