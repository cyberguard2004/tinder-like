
    telephone VARCHAR(20),
    sexe ENUM('male', 'female', 'other'),
    date_naissance DATE,
    ville VARCHAR(100),
    bio TEXT,
    photo_url VARCHAR(500),
    face_vector JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ville (ville)
);
Table: interests
sql
CREATE TABLE interests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    categorie VARCHAR(50),
    emoji VARCHAR(10),
    UNIQUE KEY uk_nom (nom)
);
Table: user_interests
sql
CREATE TABLE user_interests (
    user_id INT NOT NULL,
    interet_id INT NOT NULL,
    PRIMARY KEY (user_id, interet_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (interet_id) REFERENCES interests(id) ON DELETE CASCADE
);
Table: likes
sql
CREATE TABLE likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    type ENUM('like', 'superlike', 'dislike') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_like (from_user_id, to_user_id),
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_to_user (to_user_id),
    INDEX idx_created (created_at)
);
Table: matches
sql
CREATE TABLE matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_match (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user1 (user1_id),
    INDEX idx_user2 (user2_id)
);
Table: messages
sql
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    match_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_match (match_id),
    INDEX idx_created (created_at),
    INDEX idx_unread (match_id, is_read)
);
Table: user_photos
sql
CREATE TABLE user_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);
API Endpoints
Endpoint	Method	Description
/api/register.php	POST	Create new user account
/api/login-password.php	POST	Authenticate with email/password
/api/login-face.php	POST	Authenticate with facial recognition
/api/logout.php	POST	Destroy user session
/api/get-potential-matches.php	GET	Fetch suggested profiles
/api/like-user.php	POST	Like or superlike a user
/api/dislike-user.php	POST	Pass on a user
/api/get-matches.php	GET	Retrieve all matches
/api/get-conversations.php	GET	Get chat conversations
/api/get-messages.php	GET	Fetch messages for a match
/api/send-message.php	POST	Send a new message
/api/get-interets.php	GET	List all available interests
Data Structures Used
1. Hash Map (Object/Dictionary) - User Session Storage
javascript
// Session data stored as key-value pairs
const userSession = {
    user_id: 12345,
    user_nom: "John Doe",
    user_photo: "/path/to/photo.jpg",
    login_time: 1700000000
};
Application: Storing user session data, caching profile information, and managing real-time message states.

2. Queue - Message Polling System
javascript
class MessageQueue {
    constructor() {
        this.queue = [];
        this.processing = false;
    }
    
    enqueue(message) {
        this.queue.push(message);
        this.process();
    }
    
    async process() {
        if (this.processing) return;
        this.processing = true;
        while (this.queue.length > 0) {
            const msg = this.queue.shift();
            await this.sendMessage(msg);
        }
        this.processing = false;
    }
}
Application: Managing outgoing messages to prevent race conditions and ensure ordered delivery.

3. Stack - Card Swipe History
javascript
class SwipeHistory {
    constructor() {
        this.history = [];  // Stack implementation
    }
    
    push(profileId, action) {
        this.history.push({ profileId, action, timestamp: Date.now() });
    }
    
    undo() {
        return this.history.pop();
    }
    
    getLastAction() {
        return this.history[this.history.length - 1];
    }
}
Application: Tracking user swipe history for undo functionality and analytics.

4. Linked List - Conversation Threads
javascript
class MessageNode {
    constructor(message, senderId, timestamp) {
        this.message = message;
        this.senderId = senderId;
        this.timestamp = timestamp;
        this.next = null;
        this.prev = null;
    }
}

class ConversationLinkedList {
    constructor() {
        this.head = null;
        this.tail = null;
        this.length = 0;
    }
    
    append(message, senderId) {
        const node = new MessageNode(message, senderId, Date.now());
        if (!this.head) {
            this.head = this.tail = node;
        } else {
            this.tail.next = node;
            node.prev = this.tail;
            this.tail = node;
        }
        this.length++;
    }
}
Application: Managing chat message threads with bidirectional navigation for efficient scrolling.

5. Binary Search Tree - Profile Indexing
javascript
class ProfileBST {
    constructor() {
        this.root = null;
    }
    
    insert(profile) {
        this.root = this._insertRec(this.root, profile);
    }
    
    _insertRec(node, profile) {
        if (node === null) return new TreeNode(profile);
        if (profile.score < node.profile.score) {
            node.left = this._insertRec(node.left, profile);
        } else if (profile.score > node.profile.score) {
            node.right = this._insertRec(node.right, profile);
        }
        return node;
    }
    
    searchByScore(minScore, maxScore) {
        return this._searchRange(this.root, minScore, maxScore, []);
    }
}
Application: Indexing user profiles by compatibility score for efficient filtering and retrieval.

6. Heap (Priority Queue) - Match Recommendations
javascript
class MatchPriorityQueue {
    constructor() {
        this.heap = [];
    }
    
    enqueue(profile, priority) {
        this.heap.push({ profile, priority });
        this._bubbleUp(this.heap.length - 1);
    }
    
    dequeue() {
        const max = this.heap[0];
        const last = this.heap.pop();
        if (this.heap.length > 0) {
            this.heap[0] = last;
            this._sinkDown(0);
        }
        return max;
    }
    
    _bubbleUp(index) {
        while (index > 0) {
            const parent = Math.floor((index - 1) / 2);
            if (this.heap[parent].priority >= this.heap[index].priority) break;
            [this.heap[parent], this.heap[index]] = [this.heap[index], this.heap[parent]];
            index = parent;
        }
    }
}
Application: Prioritizing profile recommendations based on compatibility scores and user preferences.

7. Graph - Social Network Connections
javascript
class SocialGraph {
    constructor() {
        this.adjacencyList = new Map();
    }
    
    addVertex(userId) {
        if (!this.adjacencyList.has(userId)) {
            this.adjacencyList.set(userId, []);
        }
    }
    
    addEdge(user1, user2, weight = 1) {
        this.addVertex(user1);
        this.addVertex(user2);
        this.adjacencyList.get(user1).push({ node: user2, weight });
        this.adjacencyList.get(user2).push({ node: user1, weight });
    }
    
    findCommonInterests(userId, targetId) {
        const userInterests = this.getUserInterests(userId);
        const targetInterests = this.getUserInterests(targetId);
        return userInterests.filter(i => targetInterests.includes(i));
    }
    
    suggestMutualFriends(userId, depth = 2) {
        const visited = new Set();
        const queue = [{ id: userId, distance: 0 }];
        const suggestions = [];
        
        while (queue.length > 0) {
            const current = queue.shift();
            if (current.distance > depth) continue;
            
            const neighbors = this.adjacencyList.get(current.id) || [];
            for (const neighbor of neighbors) {
                if (!visited.has(neighbor.node) && neighbor.node !== userId) {
                    visited.add(neighbor.node);
                    suggestions.push({ id: neighbor.node, distance: current.distance + 1 });
                    queue.push({ id: neighbor.node, distance: current.distance + 1 });
                }
            }
        }
        return suggestions;
    }
}
Application: Modeling user connections, finding mutual friends, and suggesting second-degree connections.

8. LRU Cache - Profile Image Caching
javascript
class LRUCache {
    constructor(capacity = 50) {
        this.capacity = capacity;
        this.cache = new Map();
    }
    
    get(key) {
        if (!this.cache.has(key)) return null;
        const value = this.cache.get(key);
        this.cache.delete(key);
        this.cache.set(key, value);
        return value;
    }
    
    set(key, value) {
        if (this.cache.has(key)) {
            this.cache.delete(key);
        } else if (this.cache.size >= this.capacity) {
            const oldestKey = this.cache.keys().next().value;
            this.cache.delete(oldestKey);
        }
        this.cache.set(key, value);
    }
    
    clear() {
        this.cache.clear();
    }
}
Application: Caching profile images and user data to reduce API calls and improve performance.

9. Trie - Search Autocomplete
javascript
class TrieNode {
    constructor() {
        this.children = new Map();
        this.isEndOfWord = false;
        this.data = null;
    }
}

class Trie {
    constructor() {
        this.root = new TrieNode();
    }
    
    insert(word, data) {
        let current = this.root;
        for (const char of word.toLowerCase()) {
            if (!current.children.has(char)) {
                current.children.set(char, new TrieNode());
            }
            current = current.children.get(char);
        }
        current.isEndOfWord = true;
        current.data = data;
    }
    
    searchPrefix(prefix) {
        let current = this.root;
        for (const char of prefix.toLowerCase()) {
            if (!current.children.has(char)) return [];
            current = current.children.get(char);
        }
        return this._getAllWords(current, prefix);
    }
    
    _getAllWords(node, prefix) {
        const results = [];
        if (node.isEndOfWord) results.push({ word: prefix, data: node.data });
        for (const [char, childNode] of node.children) {
            results.push(...this._getAllWords(childNode, prefix + char));
        }
        return results;
    }
}
Application: Implementing search functionality for finding users by name or interest keywords.

10. Bloom Filter - Duplicate Prevention
javascript
class BloomFilter {
    constructor(size = 1000, hashCount = 3) {
        this.size = size;
        this.hashCount = hashCount;
        this.bitArray = new Array(size).fill(false);
    }
    
    _hash1(value) {
        let hash = 0;
        for (let i = 0; i < value.length; i++) {
            hash = ((hash << 5) - hash) + value.charCodeAt(i);
            hash |= 0;
        }
        return Math.abs(hash) % this.size;
    }
    
    _hash2(value) {
        let hash = 5381;
        for (let i = 0; i < value.length; i++) {
            hash = ((hash << 5) + hash) + value.charCodeAt(i);
        }
        return Math.abs(hash) % this.size;
    }
    
    _hash3(value) {
        let hash = 0;
        for (let i = 0; i < value.length; i++) {
            hash = (hash * 31 + value.charCodeAt(i)) % this.size;
        }
        return hash;
    }
    
    add(value) {
        const indexes = [
            this._hash1(value),
            this._hash2(value),
            this._hash3(value)
        ];
        for (let i = 0; i < this.hashCount; i++) {
            this.bitArray[indexes[i]] = true;
        }
    }
    
    contains(value) {
        const indexes = [
            this._hash1(value),
            this._hash2(value),
            this._hash3(value)
        ];
        for (let i = 0; i < this.hashCount; i++) {
            if (!this.bitArray[indexes[i]]) return false;
        }
        return true;
    }
}
Application: Preventing duplicate profile views and optimizing memory usage for viewed profile tracking.

Algorithms Used
1. Compatibility Score Algorithm
php
function calculateCompatibility($user1, $user2) {
    $score = 0;
    
    // Interest matching (40% weight)
    $commonInterests = array_intersect($user1['interests'], $user2['interests']);
    $interestScore = (count($commonInterests) / max(count($user1['interests']), 1)) * 40;
    $score += $interestScore;
    
    // Location proximity (20% weight)
    $locationScore = calculateLocationProximity($user1['ville'], $user2['ville']) * 20;
    $score += $locationScore;
    
    // Age proximity (15% weight)
    $ageDiff = abs($user1['age'] - $user2['age']);
    $ageScore = max(0, (1 - $ageDiff / 20)) * 15;
    $score += $ageScore;
    
    // Activity level matching (15% weight)
    $activityScore = compareActivityLevels($user1['last_active'], $user2['last_active']) * 15;
    $score += $activityScore;
    
    // Profile completeness (10% weight)
    $completenessScore = (calculateProfileCompleteness($user1) + calculateProfileCompleteness($user2)) / 2 * 10;
    $score += $completenessScore;
    
    return round($score, 2);
}
2. Face Recognition Algorithm (Face-API.js)
javascript
async function recognizeFace(videoElement) {
    // Load models
    await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
    
    // Detect faces
    const detection = await faceapi.detectSingleFace(videoElement)
        .withFaceLandmarks()
        .withFaceDescriptor();
    
    if (detection) {
        const descriptor = detection.descriptor;
        // Compare with stored descriptors using Euclidean distance
        const bestMatch = await findBestMatch(descriptor, storedDescriptors);
        
        if (bestMatch.distance < 0.6) {
            return { success: true, userId: bestMatch.userId };
        }
    }
    return { success: false };
}

function euclideanDistance(descriptor1, descriptor2) {
    let sum = 0;
    for (let i = 0; i < descriptor1.length; i++) {
        sum += Math.pow(descriptor1[i] - descriptor2[i], 2);
    }
    return Math.sqrt(sum);
}
3. Binary Search - Profile Filtering
javascript
function binarySearchByAge(profiles, targetAge) {
    let left = 0;
    let right = profiles.length - 1;
    let result = -1;
    
    while (left <= right) {
        const mid = Math.floor((left + right) / 2);
        if (profiles[mid].age === targetAge) {
            result = mid;
            break;
        } else if (profiles[mid].age < targetAge) {
            left = mid + 1;
        } else {
            right = mid - 1;
        }
    }
    
    // Return all profiles within age range
    const matches = [];
    if (result !== -1) {
        let i = result;
        while (i >= 0 && profiles[i].age === targetAge) matches.unshift(profiles[i--]);
        i = result + 1;
        while (i < profiles.length && profiles[i].age === targetAge) matches.push(profiles[i++]);
    }
    return matches;
}
4. Levenshtein Distance - Interest Matching
javascript
function levenshteinDistance(str1, str2) {
    const m = str1.length;
    const n = str2.length;
    const dp = Array(m + 1).fill().map(() => Array(n + 1).fill(0));
    
    for (let i = 0; i <= m; i++) dp[i][0] = i;
    for (let j = 0; j <= n; j++) dp[0][j] = j;
    
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
            dp[i][j] = Math.min(
                dp[i - 1][j] + 1,      // deletion
                dp[i][j - 1] + 1,      // insertion
                dp[i - 1][j - 1] + cost // substitution
            );
        }
    }
    return dp[m][n];
}

function findSimilarInterests(userInterest, allInterests, threshold = 2) {
    const similar = [];
    for (const interest of allInterests) {
        const distance = levenshteinDistance(userInterest.toLowerCase(), interest.toLowerCase());
        if (distance <=
explique chque deta structires avec 10 lignes puis donnees le morceaux de codes aussi des calculs mathematiques renvois tous
MatchFace Campus - Complete Data Structures & Algorithms Documentation
Table of Contents
Hash Map (Object/Dictionary)

Queue

Stack

Linked List

Binary Search Tree

Heap (Priority Queue)

Graph

LRU Cache

Trie

Bloom Filter

1. HASH MAP
Explanation (10 lines)
A Hash Map is a data structure that stores key-value pairs and provides O(1) average-time complexity for insertions, deletions, and lookups. It uses a hash function to compute an index into an array of buckets where the value is stored. Collisions occur when two different keys hash to the same index, resolved using chaining (linked lists) or open addressing. Hash maps are ideal for caching user sessions, storing profile data, and implementing associative arrays. Memory usage is efficient as storage grows dynamically with load factor management. The hash function should distribute keys uniformly to minimize collisions. In JavaScript, objects and Maps implement hash map functionality natively. This structure is fundamental for database indexing and real-time data retrieval in social applications.

Code Implementation
javascript
// Hash Map implementation for user session management
class HashMap {
    constructor(initialCapacity = 16, loadFactor = 0.75) {
        this.capacity = initialCapacity;
        this.loadFactor = loadFactor;
        this.size = 0;
        this.buckets = new Array(this.capacity).fill(null).map(() => []);
    }

    // Hash function - converts key to array index
    _hash(key) {
        let hash = 0;
        const keyStr = String(key);
        for (let i = 0; i < keyStr.length; i++) {
            // Mathematical operation: prime number multiplication for better distribution
            hash = (hash * 31 + keyStr.charCodeAt(i)) % this.capacity;
        }
        return hash;
    }

    // Insert or update key-value pair
    set(key, value) {
        const index = this._hash(key);
        const bucket = this.buckets[index];
        
        // Check if key already exists and update
        for (let i = 0; i < bucket.length; i++) {
            if (bucket[i][0] === key) {
                bucket[i][1] = value;
                return;
            }
        }
        
        // Insert new key-value pair
        bucket.push([key, value]);
        this.size++;
        
        // Resize if load factor exceeded
        if (this.size / this.capacity > this.loadFactor) {
            this._resize();
        }
    }

    // Retrieve value by key - O(1) average case
    get(key) {
        const index = this._hash(key);
        const bucket = this.buckets[index];
        
        for (let i = 0; i < bucket.length; i++) {
            if (bucket[i][0] === key) {
                return bucket[i][1];
            }
        }
        return null;
    }

    // Delete key-value pair
    delete(key) {
        const index = this._hash(key);
        const bucket = this.buckets[index];
        
        for (let i = 0; i < bucket.length; i++) {
            if (bucket[i][0] === key) {
                bucket.splice(i, 1);
                this.size--;
                return true;
            }
        }
        return false;
    }

    // Resize hash map when load factor threshold is reached
    _resize() {
        const oldBuckets = this.buckets;
        this.capacity *= 2;
        this.size = 0;
        this.buckets = new Array(this.capacity).fill(null).map(() => []);
        
        // Rehash all existing entries
        for (const bucket of oldBuckets) {
            for (const [key, value] of bucket) {
                this.set(key, value);
            }
        }
    }

    // Check if key exists
    has(key) {
        const index = this._hash(key);
        const bucket = this.buckets[index];
        return bucket.some(([k]) => k === key);
    }

    // Get all keys
    keys() {
        const keys = [];
        for (const bucket of this.buckets) {
            for (const [key] of bucket) {
                keys.push(key);
            }
        }
        return keys;
    }
}

// Mathematical calculations used in Hash Map
function calculateHashDistribution() {
    // Formula for hash collision probability (Birthday Problem)
    // P(collision) ≈ 1 - e^(-n²/(2m))
    // where n = number of items, m = number of buckets
    
    const m = 1000; // number of buckets
    const n = 500;  // number of items
    
    // Probability of at least one collision
    const probabilityCollision = 1 - Math.exp(-Math.pow(n, 2) / (2 * m));
    
    // Expected number of collisions
    const expectedCollisions = n - m * (1 - Math.pow((m - 1) / m, n));
    
    return {
        probabilityCollision: (probabilityCollision * 100).toFixed(2) + '%',
        expectedCollisions: Math.round(expectedCollisions)
    };
}

// Session management using HashMap
class SessionManager {
    constructor() {
        this.sessions = new HashMap();
        this.sessionTimeout = 3600000; // 1 hour in milliseconds
    }
    
    createSession(userId, userData) {
        const sessionId = this._generateSessionId();
        const session = {
            userId: userId,
            data: userData,
            createdAt: Date.now(),
            lastActivity: Date.now()
        };
        this.sessions.set(sessionId, session);
        
        // Schedule session cleanup
        setTimeout(() => this.cleanupExpiredSessions(), this.sessionTimeout);
        return sessionId;
    }
    
    getSession(sessionId) {
        const session = this.sessions.get(sessionId);
        if (session && (Date.now() - session.lastActivity) < this.sessionTimeout) {
            session.lastActivity = Date.now();
            this.sessions.set(sessionId, session);
            return session;
        }
        return null;
    }
    
    _generateSessionId() {
        // Mathematical: 128-bit random session ID
        // 2^128 possible combinations = 3.4 × 10^38 possibilities
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    cleanupExpiredSessions() {
        const now = Date.now();
        for (const sessionId of this.sessions.keys()) {
            const session = this.sessions.get(sessionId);
            if (now - session.lastActivity >= this.sessionTimeout) {
                this.sessions.delete(sessionId);
            }
        }
    }
}
2. QUEUE
Explanation (10 lines)
A Queue is a linear data structure that follows the First-In-First-Out (FIFO) principle, where elements are added at the rear and removed from the front. This structure is ideal for managing message processing, task scheduling, and handling asynchronous operations in order. Real-world applications include print spooling, CPU task scheduling, and breadth-first search algorithms. In chat applications, queues ensure messages are delivered in the correct chronological order. The enqueue operation adds an element to the back, while dequeue removes from the front with O(1) time complexity using linked list implementation. Circular queues optimize space utilization by reusing empty slots. Priority queues extend this concept by ordering elements by priority rather than insertion time.

Code Implementation
javascript
// Queue implementation for message processing
class Queue {
    constructor() {
        this.items = {};
        this.frontIndex = 0;
        this.rearIndex = 0;
        this.size = 0;
    }

    // Add element to the rear - O(1)
    enqueue(element) {
        this.items[this.rearIndex] = element;
        this.rearIndex++;
        this.size++;
        return this.size;
    }

    // Remove and return front element - O(1)
    dequeue() {
        if (this.isEmpty()) return null;
        
        const element = this.items[this.frontIndex];
        delete this.items[this.frontIndex];
        this.frontIndex++;
        this.size--;
        
        // Reset indices when queue becomes empty
        if (this.isEmpty()) {
            this.frontIndex = 0;
            this.rearIndex = 0;
        }
        
        return element;
    }

    // Peek at front element without removing
    peek() {
        if (this.isEmpty()) return null;
        return this.items[this.frontIndex];
    }

    // Check if queue is empty
    isEmpty() {
        return this.size === 0;
    }

    // Get current size
    getSize() {
        return this.size;
    }

    // Clear all elements
    clear() {
        this.items = {};
        this.frontIndex = 0;
        this.rearIndex = 0;
        this.size = 0;
    }

    // Convert to array for iteration
    toArray() {
        const result = [];
        for (let i = this.frontIndex; i < this.rearIndex; i++) {
            result.push(this.items[i]);
        }
        return result;
    }
}

// Circular Queue implementation for efficient space usage
class CircularQueue {
    constructor(capacity = 10) {
        this.capacity = capacity;
        this.queue = new Array(capacity);
        this.front = 0;
        this.rear = 0;
        this.size = 0;
    }

    // Mathematical modulo operation for circular behavior
    enqueue(element) {
        if (this.isFull()) {
            // Resize queue - O(n)
            this._resize();
        }
        
        this.queue[this.rear] = element;
        // Mathematical: (rear + 1) % capacity for circular wrap-around
        this.rear = (this.rear + 1) % this.capacity;
        this.size++;
        return true;
    }

    dequeue() {
        if (this.isEmpty()) return null;
        
        const element = this.queue[this.front];
        this.queue[this.front] = null;
        this.front = (this.front + 1) % this.capacity;
        this.size--;
        return element;
    }

    isFull() {
        return this.size === this.capacity;
    }

    isEmpty() {
        return this.size === 0;
    }

    _resize() {
        const newCapacity = this.capacity * 2;
        const newQueue = new Array(newCapacity);
        
        // Copy elements in correct order
        for (let i = 0; i < this.size; i++) {
            newQueue[i] = this.queue[(this.front + i) % this.capacity];
        }
        
        this.queue = newQueue;
        this.front = 0;
        this.rear = this.size;
        this.capacity = newCapacity;
    }
}

// Message Queue for chat system
class MessageQueue {
    constructor() {
        this.queue = new Queue();
        this.processing = false;
        this.messageHistory = [];
        this.maxRetries = 3;
    }

    async sendMessage(message, matchId) {
        const messageTask = {
            id: this._generateMessageId(),
            matchId: matchId,
            message: message,
            timestamp: Date.now(),
            retries: 0,
            status: 'pending'
        };
        
        this.queue.enqueue(messageTask);
        this.processQueue();
        
        return messageTask.id;
    }

    async processQueue() {
        if (this.processing) return;
        this.processing = true;
        
        while (!this.queue.isEmpty()) {
            const task = this.queue.dequeue();
            const success = await this._deliverMessage(task);
            
            if (!success && task.retries < this.maxRetries) {
                // Exponential backoff: retry after 2^retries seconds
                // Mathematical formula: delay = 2^retries * 1000 milliseconds
                const delay = Math.pow(2, task.retries) * 1000;
                task.retries++;
                task.status = 'retrying';
                
                setTimeout(() => {
                    this.queue.enqueue(task);
                }, delay);
            } else {
                task.status = success ? 'delivered' : 'failed';
                this.messageHistory.push(task);
            }
        }
        
        this.processing = false;
    }

    async _deliverMessage(task) {
        try {
            const response = await fetch('/api/send-message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    match_id: task.matchId,
                    message: task.message
                })
            });
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Message delivery failed:', error);
            return false;
        }
    }

    _generateMessageId() {
        // Mathematical: Timestamp + random component for uniqueness
        // Probability of collision: 1 in 2^128 ≈ 2.9 × 10^-39
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    getQueueStats() {
        const totalMessages = this.messageHistory.length;
        const delivered = this.messageHistory.filter(m => m.status === 'delivered').length;
        const failed = this.messageHistory.filter(m => m.status === 'failed').length;
        
        // Mathematical: Delivery success rate calculation
        const successRate = totalMessages > 0 ? (delivered / totalMessages) * 100 : 0;
        
        return {
            queueLength: this.queue.getSize(),
            totalMessages,
            delivered,
            failed,
            successRate: successRate.toFixed(2) + '%',
            averageLatency: this._calculateAverageLatency()
        };
    }

    _calculateAverageLatency() {
        const deliveredMessages = this.messageHistory.filter(m => m.status === 'delivered');
        if (deliveredMessages.length === 0) return 0;
        
        // Mathematical: Mean latency calculation
        // μ = (Σ latency) / n
        const sumLatency = deliveredMessages.reduce((sum, msg) => {
            return sum + (msg.deliveredAt - msg.timestamp);
        }, 0);
        
        return sumLatency / deliveredMessages.length;
    }
}

// Mathematical calculations for Queue analysis
function analyzeQueuePerformance() {
    // M/M/1 Queue Model (Poisson arrival, Exponential service time)
    // λ = arrival rate (messages per second)
    // μ = service rate (messages per second)
    
    const arrivalRate = 10; // 10 messages per second
    const serviceRate = 15; // 15 messages per second
    
    // Traffic intensity (utilization) - ρ = λ/μ
    const utilization = arrivalRate / serviceRate;
    
    // Average number of messages in system - L = ρ/(1-ρ)
    const avgMessagesInSystem = utilization / (1 - utilization);
    
    // Average waiting time in queue - Wq = ρ/(μ - λ)
    const avgWaitTimeQueue = utilization / (serviceRate - arrivalRate);
    
    // Average time in system - W = 1/(μ - λ)
    const avgTimeInSystem = 1 / (serviceRate - arrivalRate);
    
    // Probability of queue being empty - P0 = 1 - ρ
    const probabilityEmpty = 1 - utilization;
    
    // Probability of having n messages - Pn = ρ^n * P0
    const probabilityN = (n) => Math.pow(utilization, n) * probabilityEmpty;
    
    return {
        utilization: (utilization * 100).toFixed(2) + '%',
        avgMessagesInSystem: avgMessagesInSystem.toFixed(2),
        avgWaitTimeQueue: avgWaitTimeQueue.toFixed(2) + ' seconds',
        avgTimeInSystem: avgTimeInSystem.toFixed(2) + ' seconds',
        probabilityEmpty: (probabilityEmpty * 100).toFixed(2) + '%',
        probability5Messages: (probabilityN(5) * 100).toFixed(4) + '%'
    };
}
3. STACK
Explanation (10 lines)
A Stack is a linear data structure following Last-In-First-Out (LIFO) principle, where elements are added and removed from the same end called the top. This structure is fundamental for implementing undo/redo functionality, expression evaluation, and function call management in programming languages. In social applications, stacks track user navigation history, swipe actions, and form step progression. Push operation adds an element to the top, while pop removes the top element in O(1) time. Stack overflow occurs when trying to push onto a full stack, while underflow occurs when popping from an empty stack. The call stack in JavaScript uses this principle to manage function execution contexts. Stacks enable backtracking algorithms and depth-first search implementations.

Code Implementation
javascript
// Stack implementation for swipe history
class Stack {
    constructor(maxSize = 100) {
        this.items = [];
        this.maxSize = maxSize;
        this.top = -1;
    }

    // Push element to top - O(1)
    push(element) {
        if (this.isFull()) {
            // Remove oldest element when stack is full
            this.items.shift();
            this.top--;
        }
        this.items.push(element);
        this.top++;
        return this.size();
    }

    // Pop element from top - O(1)
    pop() {
        if (this.isEmpty()) return null;
        this.top--;
        return this.items.pop();
    }

    // Peek at top element without removing
    peek() {
        if (this.isEmpty()) return null;
        return this.items[this.items.length - 1];
    }

    // Check if stack is empty
    isEmpty() {
        return this.items.length === 0;
    }

    // Check if stack is full
    isFull() {
        return this.items.length >= this.maxSize;
    }

    // Get current size
    size() {
        return this.items.length;
    }

    // Clear all elements
    clear() {
        this.items = [];
        this.top = -1;
    }

    // Get element at specific position from top
    peekAt(index) {
        if (index < 0 || index >= this.size()) return null;
        return this.items[this.items.length - 1 - index];
    }

    // Convert to array
    toArray() {
        return [...this.items].reverse();
    }
}

// Swipe History Manager using Stack
class SwipeHistoryManager {
    constructor() {
        this.history = new Stack(50);
        this.redoStack = new Stack(50);
        this.actionTypes = {
            LIKE: 'like',
            DISLIKE: 'dislike',
            SUPERLIKE: 'superlike'
        };
    }

    recordAction(profileId, actionType, profileData) {
        const action = {
            id: this._generateActionId(),
            profileId: profileId,
            actionType: actionType,
            profileData: profileData,
            timestamp: Date.now(),
            userId: CURRENT_USER.id
        };
        
        this.history.push(action);
        this.redoStack.clear(); // Clear redo stack on new action
        
        // Log action for analytics
        this._logAction(action);
        
        return action;
    }

    undo() {
        if (this.history.isEmpty()) return null;
        
        const lastAction = this.history.pop();
        if (lastAction) {
            // Reverse the action
            this._reverseAction(lastAction);
            this.redoStack.push(lastAction);
        }
        
        return lastAction;
    }

    redo() {
        if (this.redoStack.isEmpty()) return null;
        
        const action = this.redoStack.pop();
        if (action) {
            // Re-apply the action
            this._applyAction(action);
            this.history.push(action);
        }
        
        return action;
    }

    async _reverseAction(action) {
        // Mathematical: Probability of successful undo based on action age
        const ageInSeconds = (Date.now() - action.timestamp) / 1000;
        const successProbability = Math.exp(-ageInSeconds / 3600); // Decay over 1 hour
        
        try {
            const response = await fetch('/api/undo-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action_id: action.id,
                    profile_id: action.profileId,
                    action_type: action.actionType
                })
            });
            
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Undo failed:', error);
            return false;
        }
    }

    async _applyAction(action) {
        const endpoint = action.actionType === 'like' ? '/api/like-user.php' : 
                        action.actionType === 'superlike' ? '/api/like-user.php' : 
                        '/api/dislike-user.php';
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    to_user_id: action.profileId,
                    type: action.actionType
                })
            });
            return await response.json();
        } catch (error) {
            console.error('Redo failed:', error);
            return null;
        }
    }

    _generateActionId() {
        // Mathematical: 64-bit unique identifier
        // Uses timestamp (41 bits) + random (23 bits)
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * Math.pow(2, 23));
        return (timestamp << 23) | random;
    }

    async _logAction(action) {
        // Log for analytics and ML training
        const logEntry = {
            action_id: action.id,
            user_id: action.userId,
            profile_id: action.profileId,
            action_type: action.actionType,
            timestamp: action.timestamp,
            session_id: this._getSessionId()
        };
        
        // Send to analytics service (async, non-blocking)
        fetch('/api/log-action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(logEntry)
        }).catch(err => console.error('Analytics log failed:', err));
    }

    _getSessionId() {
        return localStorage.getItem('session_id') || this._createSessionId();
    }

    _createSessionId() {
        const sessionId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
        localStorage.setItem('session_id', sessionId);
        return sessionId;
    }

    getHistoryStats() {
        const historyArray = this.history.toArray();
        const totalActions = historyArray.length;
        
        // Mathematical: Action distribution analysis
        const actionCounts = {
            like: 0,
            dislike: 0,
            superlike: 0
        };
        
        for (const action of historyArray) {
            actionCounts[action.actionType]++;
        }
        
        // Calculate entropy of action distribution
        // H(X) = -Σ p(x) * log₂ p(x)
        let entropy = 0;
        for (const count of Object.values(actionCounts)) {
            const probability = count / totalActions;
            if (probability > 0) {
                entropy -= probability * Math.log2(probability);
            }
        }
        
        return {
            totalActions,
            actionDistribution: actionCounts,
            likeRate: ((actionCounts.like / totalActions) * 100).toFixed(2) + '%',
            entropy: entropy.toFixed(4) + ' bits',
            averageActionsPerSession: this._calculateAverageActionsPerSession()
        };
    }

    _calculateAverageActionsPerSession() {
        // Mathematical: Moving average calculation
        // EMA_t = α * X_t + (1 - α) * EMA_{t-1}
        const alpha = 0.3; // Smoothing factor
        
        let ema = 0;
        const sessions = this._getSessionHistory();
        
        for (let i = 0; i < sessions.length; i++) {
            ema = alpha * sessions[i].actionCount + (1 - alpha) * ema;
        }
        
        return ema.toFixed(2);
    }

    _getSessionHistory() {
        // Retrieve session history from localStorage or API
        const history = localStorage.getItem('session_history');
        return history ? JSON.parse(history) : [];
    }
}

// Expression evaluator using Stack (for mathematical calculations)
class ExpressionEvaluator {
    constructor() {
        this.operators = {
            '+': { precedence: 1, associativity: 'L', evaluate: (a, b) => a + b },
            '-': { precedence: 1, associativity: 'L', evaluate: (a, b) => a - b },
            '*': { precedence: 2, associativity: 'L', evaluate: (a, b) => a * b },
            '/': { precedence: 2, associativity: 'L', evaluate: (a, b) => a / b },
            '^': { precedence: 3, associativity: 'R', evaluate: (a, b) => Math.pow(a, b) }
        };
    }

    // Convert infix expression to postfix using Shunting-yard algorithm
    infixToPostfix(expression) {
        const output = [];
        const operators = new Stack();
        
        for (let i = 0; i < expression.length; i++) {
            const token = expression[i];
            
            if (this.isNumber(token)) {
                output.push(token);
            } else if (this.isOperator(token)) {
                while (!operators.isEmpty() && 
                       this.isOperator(operators.peek()) &&
                       ((this.operators[token].associativity === 'L' && 
                         this.operators[token].precedence <= this.operators[operators.peek()].precedence) ||
                        (this.operators[token].associativity === 'R' && 
                         this.operators[token].precedence < this.operators[operators.peek()].precedence))) {
                    output.push(operators.pop());
                }
                operators.push(token);
            } else if (token === '(') {
                operators.push(token);
            } else if (token === ')') {
                while (!operators.isEmpty() && operators.peek() !== '(') {
                    output.push(operators.pop());
                }
                operators.pop(); // Remove '('
            }
        }
        
        while (!operators.isEmpty()) {
            output.push(operators.pop());
        }
        
        return output;
    }

    // Evaluate postfix expression using Stack
    evaluatePostfix(postfix) {
        const stack = new Stack();
        
        for (const token of postfix) {
            if (this.isNumber(token)) {
                stack.push(parseFloat(token));
            } else if (this.isOperator(token)) {
                const b = stack.pop();
                const a = stack.pop();
                const result = this.operators[token].evaluate(a, b);
                stack.push(result);
            }
        }
        
        return stack.pop();
    }

    isNumber(token) {
        return !isNaN(token) && !isNaN(parseFloat(token));
    }

    isOperator(token) {
        return token in this.operators;
    }

    // Calculate compatibility score using mathematical formula
    calculateCompatibilityScore(user1, user2) {
        // Weighted scoring formula
        // Score = w1*I + w2*L + w3*A + w4*C
        
        const weights = {
            interests: 0.4,
            location: 0.2,
            age: 0.15,
            activity: 0.15,
            completeness: 0.1
        };
        
        // Interest similarity using Jaccard index
        // J(A,B) = |A ∩ B| / |A ∪ B|
        const commonInterests = user1.interests.filter(i => user2.interests.includes(i));
        const jaccardIndex = commonInterests.length / 
                             (new Set([...user1.interests, ...user2.interests])).size;
        const interestScore = jaccardIndex * 100;
        
        // Location proximity using Haversine formula
        const locationScore = this._haversineDistance(user1.location, user2.location);
        
        // Age difference using Gaussian function
        // f(x) = e^(-(x-μ)²/(2σ²))
        const ageDiff = Math.abs(user1.age - user2.age);
        const ageScore = Math.exp(-Math.pow(ageDiff, 2) / (2 * Math.pow(5, 2))) * 100;
        
        // Activity matching using cosine similarity
        const activityScore = this._cosineSimilarity(user1.activityVector, user2.activityVector) * 100;
        
        // Profile completeness score
        const completenessScore = (user1.completeness + user2.completeness) / 2;
        
        // Final weighted score
        const finalScore = (weights.interests * interestScore) +
                          (weights.location * locationScore) +
                          (weights.age * ageScore) +
                          (weights.activity * activityScore) +
                          (weights.completeness * completenessScore);
        
        return Math.round(finalScore);
    }

    _haversineDistance(loc1, loc2) {
        // Haversine formula for great-circle distance
        // a = sin²(Δφ/2) + cos φ1 ⋅ cos φ2 ⋅ sin²(Δλ/2)
        // c = 2 ⋅ atan2(√a, √(1−a))
        // d = R ⋅ c
        
        const R = 6371; // Earth's radius in km
        const φ1 = loc1.lat * Math.PI / 180;
        const φ2 = loc2.lat * Math.PI / 180;
        const Δφ = (loc2.lat - loc1.lat) * Math.PI / 180;
        const Δλ = (loc2.lng - loc1.lng) * Math.PI / 180;
        
        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        
        // Convert distance to score (0-100, higher is better)
        return Math.max(0, 100 - (distance / 10));
    }

    _cosineSimilarity(vectorA, vectorB) {
        // Cosine similarity formula
        // cos(θ) = (A·B) / (||A|| ||B||)
        
        let dotProduct = 0;
        let magnitudeA = 0;
        let magnitudeB = 0;
        
        for (let i = 0; i < vectorA.length; i++) {
            dotProduct += vectorA[i] * vectorB[i];
            magnitudeA += Math.pow(vectorA[i], 2);
            magnitudeB += Math.pow(vectorB[i], 2);
        }
        
        magnitudeA = Math.sqrt(magnitudeA);
        magnitudeB = Math.sqrt(magnitudeB);
        
        if (magnitudeA === 0 || magnitudeB === 0) return 0;
        return dotProduct / (magnitudeA * magnitudeB);
    }
}
4. LINKED LIST
Explanation (10 lines)
A Linked List is a linear data structure where elements (nodes) are stored non-contiguously, connected via pointers or references. Each node contains data and a reference to the next node (singly linked) or both next and previous nodes (doubly linked). Insertion and deletion operations are O(1) when the position is known, unlike arrays which require O(n) shifting. Memory is allocated dynamically, making linked lists more efficient for frequently changing data. In chat applications, linked lists manage message threads, allowing efficient insertion of new messages and bidirectional traversal. The overhead of storing references makes them less memory-efficient than arrays for static data. Circular linked lists connect the last node to the first, useful for implementing round-robin schedulers.

Code Implementation
javascript
// Node class for Linked List
class ListNode {
    constructor(data) {
        this.data = data;
        this.next = null;
        this.prev = null;
    }
}

// Doubly Linked List implementation for message threads
class DoublyLinkedList {
    constructor() {
        this.head = null;
        this.tail = null;
        this.length = 0;
    }

    // Add node to end - O(1)
    append(data) {
        const newNode = new ListNode(data);
        
        if (this.isEmpty()) {
            this.head = newNode;
            this.tail = newNode;
        } else {
            newNode.prev = this.tail;
            this.tail.next = newNode;
            this.tail = newNode;
        }
        
        this.length++;
        return newNode;
    }

    // Add node to beginning - O(1)
    prepend(data) {
        const newNode = new ListNode(data);
        
        if (this.isEmpty()) {
            this.head = newNode;
            this.tail = newNode;
        } else {
            newNode.next = this.head;
            this.head.prev = newNode;
            this.head = newNode;
        }
        
        this.length++;
        return newNode;
    }

    // Insert at specific position - O(n)
    insertAt(position, data) {
        if (position < 0 || position > this.length) return null;
        
        if (position === 0) return this.prepend(data);
        if (position === this.length) return this.append(data);
        
        const newNode = new ListNode(data);
        let current = this.head;
        
        for (let i = 0; i < position - 1; i++) {
            current = current.next;
        }
        
        newNode.prev = current;
        newNode.next = current.next;
        current.next.prev = newNode;
        current.next = newNode;
        
        this.length++;
        return newNode;
    }

    // Remove node by value - O(n)
    remove(value) {
        if (this.isEmpty()) return null;
        
        let current = this.head;
        
        while (current !== null && current.data !== value) {
            current = current.next;
        }
        
        if (current === null) return null;
        
        if (current === this.head) {
            this.head = current.next;
            if (this.head) this.head.prev = null;
        } else if (current === this.tail) {
            this.tail = current.prev;
            this.tail.next = null;
        } else {
            current.prev.next = current.next;
            current.next.prev = current.prev;
        }
        
        this.length--;
        return current.data;
    }

    // Remove at specific position - O(n)
    removeAt(position) {
        if (position < 0 || position >= this.length) return null;
        
        let current;
        
        if (position === 0) {
            current = this.head;
            this.head = current.next;
            if (this.head) this.head.prev = null;
        } else if (position === this.length - 1) {
            current = this.tail;
            this.tail = current.prev;
            this.tail.next = null;
        } else {
            current = this.getNodeAt(position);
            current.prev.next = current.next;
            current.next.prev = current.prev;
        }
        
        this.length--;
        return current.data;
    }

    // Get node at position - O(n)
    getNodeAt(position) {
        if (position < 0 || position >= this.length) return null;
        
        let current = this.head;
        for (let i = 0; i < position; i++) {
            current = current.next;
        }
        return current;
    }

    // Find node index by value - O(n)
    indexOf(value) {
        let current = this.head;
        let index = 0;
        
        while (current !== null) {
            if (current.data === value) return index;
            current = current.next;
            index++;
        }
        
        return -1;
    }

    isEmpty() {
        return this.length === 0;
    }

    toArray() {
        const array = [];
        let current = this.head;
        
        while (current !== null) {
            array.push(current.data);
            current = current.next;
        }
        
        return array;
    }

    toReverseArray() {
        const array = [];
        let current = this.tail;
        
        while (current !== null) {
            array.push(current.data);
            current = current.prev;
        }
        
        return array;
    }

    clear() {
        this.head = null;
        this.tail = null;
        this.length = 0;
    }
}

// Message Thread Manager using Linked List
class MessageThreadManager {
    constructor(matchId) {
        this.matchId = matchId;
        this.messages = new DoublyLinkedList();
        this.unreadCount = 0;
        this.loadingThreshold = 50; // Load messages in batches of 50
        this.isLoading = false;
    }

    async loadMessages(limit = 50, offset = 0) {
        if (this.isLoading) return;
        this.isLoading = true;
        
        try {
            const response = await fetch(`/api/get-messages.php?match_id=${this.matchId}&limit=${limit}&offset=${offset}`);
            const data = await response.json();
            
            if (data.success && data.messages) {
                // Mathematical: Message loading efficiency
                // Batch loading reduces network requests by factor of (total/limit)
                const batchEfficiency = data.totalMessages / limit;
                
                if (offset === 0) {
                    // First load, clear existing
                    this.messages.clear();
                }
                
                for (const message of data.messages) {
                    this.messages.append(message);
                }
                
                this.updateUnreadCount();
                console.log(`Loaded ${data.messages.length} messages. Efficiency: ${batchEfficiency.toFixed(2)}x`);
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
        } finally {
            this.isLoading = false;
        }
    }

    addMessage(message, isSentByCurrentUser = false) {
        const messageNode = {
            id: this._generateMessageId(),
            message: message,
            senderId: isSentByCurrentUser ? CURRENT_USER.id : this._getOtherUserId(),
            timestamp: Date.now(),
            isRead: isSentByCurrentUser
        };
        
        this.messages.append(messageNode);
        
        if (!isSentByCurrentUser) {
            this.unreadCount++;
        }
        
        return messageNode;
    }

    markAsRead(messageId) {
        let current = this.messages.head;
        
        while (current !== null) {
            if (current.data.id === messageId && !current.data.isRead) {
                current.data.isRead = true;
                this.unreadCount--;
                return true;
            }
            current = current.next;
        }
        
        return false;
    }

    markAllAsRead() {
        let current = this.messages.head;
        let marked = 0;
        
        while (current !== null) {
            if (!current.data.isRead && current.data.senderId !== CURRENT_USER.id) {
                current.data.isRead = true;
                marked++;
            }
            current = current.next;
        }
        
        this.unreadCount = 0;
        
        // Send API request to mark all as read
        fetch('/api/mark-messages-read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: this.matchId })
        });
        
        return marked;
    }

    getMessagesForRendering(limit = null) {
        let messages = this.messages.toArray();
        
        if (limit && limit < messages.length) {
            messages = messages.slice(-limit);
        }
        
        return messages;
    }

    getMessagesByDate(date) {
        const filtered = [];
        let current = this.messages.head;
        
        while (current !== null) {
            const messageDate = new Date(current.data.timestamp).toDateString();
            if (messageDate === date.toDateString()) {
                filtered.push(current.data);
            }
            current = current.next;
        }
        
        return filtered;
    }

    updateUnreadCount() {
        let count = 0;
        let current = this.messages.head;
        
        while (current !== null) {
            if (!current.data.isRead && current.data.senderId !== CURRENT_USER.id) {
                count++;
            }
            current = current.next;
        }
        
        this.unreadCount = count;
        return count;
    }

    _generateMessageId() {
        // Mathematical: Deterministic ID generation using timestamp and random
        // 64-bit ID: 41 bits timestamp + 23 bits random
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * Math.pow(2, 23));
        return (timestamp << 23) | random;
    }

    _getOtherUserId() {
        // Would fetch the other user ID from match data
        return 0; // Placeholder
    }

    getStatistics() {
        const totalMessages = this.messages.length;
        const messagesArray = this.messages.toArray();
        
        // Calculate average response time
        let totalResponseTime = 0;
        let responseCount = 0;
        let lastTimestamp = null;
        let lastSenderId = null;
        
        for (const message of messagesArray) {
            if (lastSenderId !== null && message.senderId !== lastSenderId) {
                const responseTime = message.timestamp - lastTimestamp;
                totalResponseTime += responseTime;
                responseCount++;
            }
            lastTimestamp = message.timestamp;
            lastSenderId = message.senderId;
        }
        
        const avgResponseTime = responseCount > 0 ? totalResponseTime / responseCount : 0;
        
        // Calculate message frequency (messages per day)
        if (totalMessages < 2) {
            return {
                totalMessages,
                unreadCount: this.unreadCount,
                readRate: 0,
                avgResponseTime: 0,
                messagesPerDay: 0,
                conversationLength: totalMessages
            };
        }
        
        const firstMessage = messagesArray[0];
        const lastMessage = messagesArray[messagesArray.length - 1];
        const timeSpanDays = (lastMessage.timestamp - firstMessage.timestamp) / (1000 * 60 * 60 * 24);
        const messagesPerDay = timeSpanDays > 0 ? totalMessages / timeSpanDays : totalMessages;
        
        const readMessages = messagesArray.filter(m => m.isRead).length;
        const readRate = totalMessages > 0 ? (readMessages / totalMessages) * 100 : 0;
        
        return {
            totalMessages,
            unreadCount: this.unreadCount,
            readRate: readRate.toFixed(2) + '%',
            avgResponseTime: (avgResponseTime / 1000).toFixed(2) + ' seconds',
            messagesPerDay: messagesPerDay.toFixed(2),
            conversationLength: totalMessages
        };
    }
}

// Mathematical calculations for Linked List performance
function analyzeLinkedListPerformance() {
    // Mathematical models for linked list vs array operations
    
    // Memory overhead calculation
    // Linked list: O(n) with overhead per node (2 pointers = 16 bytes on 64-bit)
    // Array: O(n) contiguous memory
    
    const n = 10000; // Number of elements
    
    // Memory usage calculation
    const arrayMemory = n * 8; // 8 bytes per element (assuming number)
    const linkedListMemory = n * (8 + 16); // 8 bytes data + 16 bytes pointers
    
    // Insertion cost comparison
    // Array insert at beginning: O(n) - shifts n elements
    // Linked list insert at beginning: O(1)
    
    const arrayInsertCost = n; // Must shift n elements
    const linkedListInsertCost = 1; // Constant time
    
    // Search time comparison
    // Array: O(log n) for binary search (sorted)
    // Linked list: O(n) for linear search
    
    const arraySearchTime = Math.log2(n);
    const linkedListSearchTime = n;
    
    // Cache locality effect (approximate)
    // Arrays have better cache locality (factor of ~10x for sequential access)
    const cacheLocalityFactor = 10;
    
    return {
        memoryUsage: {
            array: (arrayMemory / 1024).toFixed(2) + ' KB',
            linkedList: (linkedListMemory / 1024).toFixed(2) + ' KB',
            overhead: ((linkedListMemory - arrayMemory) / arrayMemory * 100).toFixed(2) + '%'
        },
        insertionCost: {
            array: `O(${arrayInsertCost}) = ${arrayInsertCost} operations`,
            linkedList: `O(${linkedListInsertCost}) = ${linkedListInsertCost} operation`
        },
        searchTime: {
            array: `O(log n) ≈ ${arraySearchTime.toFixed(2)} comparisons`,
            linkedList: `O(n) = ${linkedListSearchTime} comparisons`
        },
        recommendation: arrayInsertCost > 1000 ? 
            "Linked list recommended for frequent insertions" : 
            "Array recommended for sequential access"
    };
}
5. BINARY SEARCH TREE
Explanation (10 lines)
A Binary Search Tree (BST) is a hierarchical data structure where each node has at most two children, with left children containing smaller values and right children containing larger values. This property enables O(log n) average-case time complexity for search, insertion, and deletion operations. BSTs are ideal for implementing ordered maps, database indexes, and maintaining sorted data for quick retrieval. In social applications, BSTs can index user profiles by compatibility score, age, or location for efficient filtering. Balanced variants like AVL trees or Red-Black trees prevent degradation to O(n) in worst-case scenarios. Tree traversal algorithms (in-order, pre-order, post-order) provide different ordering of the data. The height of a balanced tree is approximately log₂(n), ensuring efficient operations even with large datasets.

Code Implementation
javascript
// TreeNode class for BST
class TreeNode {
    constructor(key, value = null) {
        this.key = key;
        this.value = value;
        this.left = null;
        this.right = null;
        this.height = 1; // For AVL balancing
        this.size = 1;   // Subtree size for order statistics
    }
}

// Self-Balancing AVL Tree implementation
class AVLTree {
    constructor() {
        this.root = null;
        this.comparator = (a, b) => a - b;
    }

    // Get node height
    getHeight(node) {
        return node ? node.height : 0;
    }

    // Update node height
    updateHeight(node) {
        if (node) {
            node.height = 1 + Math.max(this.getHeight(node.left), this.getHeight(node.right));
        }
    }

    // Get balance factor
    getBalance(node) {
        return node ? this.getHeight(node.left) - this.getHeight(node.right) : 0;
    }

    // Right rotation
    rotateRight(y) {
        const x = y.left;
        const T2 = x.right;
        
        // Perform rotation
        x.right = y;
        y.left = T2;
        
        // Update heights
        this.updateHeight(y);
        this.updateHeight(x);
        
        // Update sizes
        this.updateSize(y);
        this.updateSize(x);
        
        return x;
    }

    // Left rotation
    rotateLeft(x) {
        const y = x.right;
        const T2 = y.left;
        
        // Perform rotation
        y.left = x;
        x.right = T2;
        
        // Update heights
        this.updateHeight(x);
        this.updateHeight(y);
        
        // Update sizes
        this.updateSize(x);
        this.updateSize(y);
        
        return y;
    }

    // Update subtree size
    updateSize(node) {
        if (node) {
            node.size = 1 + this.getSize(node.left) + this.getSize(node.right);
        }
    }

    getSize(node) {
        return node ? node.size : 0;
    }

    // Insert key-value pair
    insert(key, value) {
        this.root = this._insert(this.root, key, value);
        return this;
    }

    _insert(node, key, value) {
        // Standard BST insert
        if (!node) {
            return new TreeNode(key, value);
        }
        
        if (this.comparator(key, node.key) < 0) {
            node.left = this._insert(node.left, key, value);
        } else if (this.comparator(key, node.key) > 0) {
            node.right = this._insert(node.right, key, value);
        } else {
            node.value = value; // Update value if key exists
            return node;
        }
        
        // Update height and size
        this.updateHeight(node);
        this.updateSize(node);
        
        // Get balance factor
        const balance = this.getBalance(node);
        
        // Balance the tree (AVL rotations)
        
        // Left Left Case
        if (balance > 1 && this.comparator(key, node.left.key) < 0) {
            return this.rotateRight(node);
        }
        
        // Right Right Case
        if (balance < -1 && this.comparator(key, node.right.key) > 0) {
            return this.rotateLeft(node);
        }
        
        // Left Right Case
        if (balance > 1 && this.comparator(key, node.left.key) > 0) {
            node.left = this.rotateLeft(node.left);
            return this.rotateRight(node);
        }
        
        // Right Left Case
        if (balance < -1 && this.comparator(key, node.right.key) < 0) {
            node.right = this.rotateRight(node.right);
            return this.rotateLeft(node);
        }
        
        return node;
    }

    // Search for key
    search(key) {
        let current = this.root;
        
        while (current) {
            if (this.comparator(key, current.key) === 0) {
                return current.value;
            } else if (this.comparator(key, current.key) < 0) {
                current = current.left;
            } else {
                current = current.right;
            }
        }
        
        return null;
    }

    // Find minimum key
    findMin() {
        let current = this.root;
        while (current && current.left) {
            current = current.left;
        }
        return current ? current.key : null;
    }

    // Find maximum key
    findMax() {
        let current = this.root;
        while (current && current.right) {
            current = current.right;
        }
        return current ? current.key : null;
    }

    // Range query - find all keys between low and high
    rangeQuery(low, high) {
        const results = [];
        this._rangeQuery(this.root, low, high, results);
        return results;
    }

    _rangeQuery(node, low, high, results) {
        if (!node) return;
        
        if (this.comparator(low, node.key) < 0) {
            this._rangeQuery(node.left, low, high, results);
        }
        
        if (this.comparator(low, node.key) <= 0 && this.comparator(node.key, high) <= 0) {
            results.push({ key: node.key, value: node.value });
        }
        
        if (this.comparator(high, node.key) > 0) {
            this._rangeQuery(node.right, low, high, results);
        }
    }

    // Find k-th smallest element (order statistic)
    kthSmallest(k) {
        if (k < 1 || k > this.getSize(this.root)) return null;
        return this._kthSmallest(this.root, k);
    }

    _kthSmallest(node, k) {
        const leftSize = this.getSize(node.left);
        
        if (k <= leftSize) {
            return this._kthSmallest(node.left, k);
        } else if (k === leftSize + 1) {
            return { key: node.key, value: node.value };
        } else {
            return this._kthSmallest(node.right, k - leftSize - 1);
        }
    }

    // In-order traversal (sorted order)
    inorder() {
        const result = [];
        this._inorder(this.root, result);
        return result;
    }

    _inorder(node, result) {
        if (node) {
            this._inorder(node.left, result);
            result.push({ key: node.key, value: node.value });
            this._inorder(node.right, result);
        }
    }

    // Pre-order traversal
    preorder() {
        const result = [];
        this._preorder(this.root, result);
        return result;
    }

    _preorder(node, result) {
        if (node) {
            result.push({ key: node.key, value: node.value });
            this._preorder(node.left, result);
            this._preorder(node.right, result);
        }
    }

    // Post-order traversal
    postorder() {
        const result = [];
        this._postorder(this.root, result);
        return result;
    }

    _postorder(node, result) {
        if (node) {
            this._postorder(node.left, result);
            this._postorder(node.right, result);
            result.push({ key: node.key, value: node.value });
        }
    }

    // Delete key
    delete(key) {
        this.root = this._delete(this.root, key);
        return this;
    }

    _delete(node, key) {
        if (!node) return null;
        
        if (this.comparator(key, node.key) < 0) {
            node.left = this._delete(node.left, key);
        } else if (this.comparator(key, node.key) > 0) {
            node.right = this._delete(node.right, key);
        } else {
            // Node to be deleted found
            
            // Case 1: No child or one child
            if (!node.left || !node.right) {
                const child = node.left || node.right;
                return child;
            }
            
            // Case 2: Two children
            // Find inorder successor (smallest in right subtree)
            let successor = node.right;
            while (successor.left) {
                successor = successor.left;
            }
            
            // Copy successor data to node
            node.key = successor.key;
            node.value = successor.value;
            
            // Delete the successor
            node.right = this._delete(node.right, successor.key);
        }
        
        // Update height and size
        this.updateHeight(node);
        this.updateSize(node);
        
        // Rebalance
        const balance = this.getBalance(node);
        
        // Left Left Case
        if (balance > 1 && this.getBalance(node.left) >= 0) {
            return this.rotateRight(node);
        }
        
        // Left Right Case
        if (balance > 1 && this.getBalance(node.left) < 0) {
            node.left = this.rotateLeft(node.left);
            return this.rotateRight(node);
        }
        
        // Right Right Case
        if (balance < -1 && this.getBalance(node.right) <= 0) {
            return this.rotateLeft(node);
        }
        
        // Right Left Case
        if (balance < -1 && this.getBalance(node.right) > 0) {
            node.right = this.rotateRight(node.right);
            return this.rotateLeft(node);
        }
        
        return node;
    }
}

// Profile Index using BST for compatibility scoring
class ProfileIndex {
    constructor() {
        this.indexByScore = new AVLTree();
        this.indexByAge = new AVLTree();
        this.indexByLocation = new AVLTree();
        this.profiles = new Map(); // Quick lookup by ID
    }

    addProfile(profile) {
        const compatibilityScore = this._calculateCompatibilityScore(profile);
        
        this.indexByScore.insert(compatibilityScore, profile);
        this.indexByAge.insert(profile.age, profile);
        this.indexByLocation.insert(this._hashLocation(profile.location), profile);
        this.profiles.set(profile.id, profile);
        
        return compatibilityScore;
    }

    removeProfile(profileId) {
        const profile = this.profiles.get(profileId);
        if (!profile) return false;
        
        const compatibilityScore = this._calculateCompatibilityScore(profile);
        
        this.indexByScore.delete(compatibilityScore);
        this.indexByAge.delete(profile.age);
        this.indexByLocation.delete(this._hashLocation(profile.location));
        this.profiles.delete(profileId);
        
        return true;
    }

    getTopProfiles(limit = 10, minScore = 0) {
        // Mathematical: Get top K profiles by score
        // Using kth smallest to get the K highest (since tree is sorted ascending)
        const total = this.indexByScore.getSize(this.indexByScore.root);
        const start = Math.max(1, total - limit + 1);
        
        const results = [];
        for (let i = start; i <= total; i++) {
            const result = this.indexByScore.kthSmallest(i);
            if (result && result.value && this._getProfileScore(result.value) >= minScore) {
                results.push(result.value);
            }
        }
        
        return results.reverse(); // Highest score first
    }

    searchByAgeRange(minAge, maxAge) {
        const profiles = [];
        const rangeResults = this.indexByAge.rangeQuery(minAge, maxAge);
        
        for (const result of rangeResults) {
            profiles.push(result.value);
        }
        
        return profiles;
    }

    searchByLocationRadius(centerLocation, radiusKm) {
        const results = [];
        const hashPrefix = this._hashLocationPrefix(centerLocation, radiusKm);
        
        // Range query on location hash prefix
        const rangeResults = this.indexByLocation.rangeQuery(
            hashPrefix.min, 
            hashPrefix.max
        );
        
        for (const result of rangeResults) {
            const profile = result.value;
            const distance = this._haversineDistance(centerLocation, profile.location);
            if (distance <= radiusKm) {
                results.push(profile);
            }
        }
        
        return results;
    }

    getNearestProfiles(centerLocation, limit = 10) {
        const allProfiles = Array.from(this.profiles.values());
        
        // Mathematical: Calculate distance for each profile
        for (const profile of allProfiles) {
            profile._distance = this._haversineDistance(centerLocation, profile.location);
        }
        
        // Sort by distance (using quicksort - O(n log n))
        allProfiles.sort((a, b) => a._distance - b._distance);
        
        return allProfiles.slice(0, limit);
    }

    _calculateCompatibilityScore(profile) {
        // Weighted scoring algorithm
        // Mathematical formula: Σ(w_i * f_i(x))
        
        const weights = {
            completeness: 0.15,
            activity: 0.25,
            interests: 0.35,
            engagement: 0.25
        };
        
        const completenessScore = this._calculateCompleteness(profile) * weights.completeness;
        const activityScore = this._calculateActivityScore(profile) * weights.activity;
        const interestScore = this._calculateInterestScore(profile) * weights.interests;
        const engagementScore = this._calculateEngagementScore(profile) * weights.engagement;
        
        return completenessScore + activityScore + interestScore + engagementScore;
    }

    _calculateCompleteness(profile) {
        // Mathematical: Profile completeness percentage
        const fields = ['bio', 'photo_url', 'ville', 'telephone', 'interests'];
        let completed = 0;
        
        for (const field of fields) {
            if (profile[field] && profile[field].length > 0) {
                completed++;
            }
        }
        
        return completed / fields.length;
    }

    _calculateActivityScore(profile) {
        // Mathematical: Exponential decay for activity recency
        // f(t) = e^(-λt) where λ = 1/7 (one week decay constant)
        
        const lambda = 1 / 7; // Decay over 7 days
        const daysSinceActive = (Date.now() - new Date(profile.last_active).getTime()) / (1000 * 60 * 60 * 24);
        
        return Math.exp(-lambda * daysSinceActive);
    }

    _calculateInterestScore(profile) {
        // Mathematical: Interest diversity and specificity
        // Shannon entropy for interest distribution
        
        const interests = profile.interests || [];
        if (interests.length === 0) return 0;
        
        // Calculate interest category distribution
        const categoryCounts = {};
        for (const interest of interests) {
            const category = interest.categorie || 'Other';
            categoryCounts[category] = (categoryCounts[category] || 0) + 1;
        }
        
        // Calculate entropy
        let entropy = 0;
        for (const count of Object.values(categoryCounts)) {
            const probability = count / interests.length;
            entropy -= probability * Math.log2(probability);
        }
        
        // Normalize entropy (max entropy for 5 categories = log2(5) ≈ 2.32)
        const maxEntropy = Math.log2(Object.keys(categoryCounts).length);
        const normalizedEntropy = maxEntropy > 0 ? entropy / maxEntropy : 1;
        
        // Combine with interest count (normalized to 0-1)
        const countScore = Math.min(1, interests.length / 10);
        
        return (normalizedEntropy + countScore) / 2;
    }

    _calculateEngagementScore(profile) {
        // Mathematical: Engagement rate calculation
        // Engagement = (likes_received + matches + messages_sent) / time_active_days
        
        const daysActive = Math.max(1, (Date.now() - new Date(profile.created_at).getTime()) / (1000 * 60 * 60 * 24));
        const totalEngagement = (profile.likes_received || 0) + 
                               (profile.matches || 0) + 
                               (profile.messages_sent || 0);
        
        const engagementRate = totalEngagement / daysActive;
        
        // Normalize to 0-1 (assuming max 10 engagements per day)
        return Math.min(1, engagementRate / 10);
    }

    _hashLocation(location) {
        // Mathematical: Geohash-like encoding
        // Convert lat/lng to a single integer for range queries
        const precision = 10000; // 4 decimal places ≈ 11m accuracy
        const latInt = Math.floor(location.lat * precision);
        const lngInt = Math.floor(location.lng * precision);
        
        // Interleave bits for spatial locality (Z-order curve)
        return this._interleaveBits(latInt, lngInt);
    }

    _hashLocationPrefix(centerLocation, radiusKm) {
        // Mathematical: Calculate geohash prefix range
        // Approximate: 1° latitude ≈ 111km
        const latOffset = radiusKm / 111;
        const lngOffset = radiusKm / (111 * Math.cos(centerLocation.lat * Math.PI / 180));
        
        const minLat = centerLocation.lat - latOffset;
        const maxLat = centerLocation.lat + latOffset;
        const minLng = centerLocation.lng - lngOffset;
        const maxLng = centerLocation.lng + lngOffset;
        
        const precision = 10000;
        
        return {
            min: this._interleaveBits(Math.floor(minLat * precision), Math.floor(minLng * precision)),
            max: this._interleaveBits(Math.floor(maxLat * precision), Math.floor(maxLng * precision))
        };
    }

    _interleaveBits(x, y) {
        // Mathematical: Morton code (Z-order curve)
        // Interleaves bits of two integers
        let result = 0;
        for (let i = 0; i < 32; i++) {
            result |= ((x >> i) & 1) << (2 * i);
            result |= ((y >> i) & 1) << (2 * i + 1);
        }
        return result;
    }

    _haversineDistance(loc1, loc2) {
        const R = 6371; // Earth's radius in km
        const φ1 = loc1.lat * Math.PI / 180;
        const φ2 = loc2.lat * Math.PI / 180;
        const Δφ = (loc2.lat - loc1.lat) * Math.PI / 180;
        const Δλ = (loc2.lng - loc1.lng) * Math.PI / 180;
        
        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        return R * c;
    }

    _getProfileScore(profile) {
        return this._calculateCompatibilityScore(profile);
    }

    getStatistics() {
        const totalProfiles = this.profiles.size;
        const treeHeight = this.indexByScore.root ? this.indexByScore.root.height : 0;
        
        // Mathematical: Tree balance ratio
        const idealHeight = Math.ceil(Math.log2(totalProfiles + 1));
        const balanceRatio = idealHeight > 0 ? treeHeight / idealHeight : 1;
        
        // Mathematical: Search complexity
        const avgSearchComplexity = Math.log2(totalProfiles);
        
        return {
            totalProfiles,
            treeHeight,
            idealHeight,
            balanceRatio: balanceRatio.toFixed(2),
            avgSearchComplexity: `O(log n) ≈ ${avgSearchComplexity.toFixed(2)} comparisons`,
            memoryUsage: this._calculateMemoryUsage(),
            profilesByScore: this._getScoreDistribution()
        };
    }

    _calculateMemoryUsage() {
        // Mathematical: Estimate memory usage in bytes
        const profilesPerNode = 1;
        const overheadPerNode = 48; // Pointers + metadata
        const profileSize = 200; // Approximate profile size in bytes
        
        const totalProfiles = this.profiles.size;
        const totalBytes = totalProfiles * (profileSize + overheadPerNode);
        
        return (totalBytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    _getScoreDistribution() {
        const distribution = {
            '0-20': 0,
            '21-40': 0,
            '41-60': 0,
            '61-80': 0,
            '81-100': 0
        };
        
        const scores = this.indexByScore.inorder();
        for (const item of scores) {
            const score = item.key;
            if (score <= 20) distribution['0-20']++;
            else if (score <= 40) distribution['21-40']++;
            else if (score <= 60) distribution['41-60']++;
            else if (score <= 80) distribution['61-80']++;
            else distribution['81-100']++;
        }
        
        return distribution;
    }
}

// Mathematical analysis of BST performance
function analyzeBSTPerformance() {
    // Mathematical formulas for BST analysis
    
    // Expected height of random BST: E[H] ≈ 4.5 ln n
    // Worst-case height: O(n)
    // Average-case height: O(log n)
    
    const n = 10000; // Number of nodes
    
    // Expected height calculation
    const expectedHeight = 4.5 * Math.log(n);
    
    // Probability of worst-case (sorted insertion)
    // P(worst-case) = 2^(n-1) / n! ≈ extremely small for large n
    const logProbWorstCase = (n - 1) * Math.log(2) - this._logFactorial(n);
    
    // Average number of comparisons for search
    const avgComparisons = 1.39 * Math.log2(n); // Constant factor from analysis
    
    // Space complexity
    const spaceComplexity = n * (2 * 8 + 8); // 2 pointers + data ≈ 24 bytes per node
    
    return {
        expectedHeight: expectedHeight.toFixed(2),
        worstCaseHeight: n,
        avgComparisons: avgComparisons.toFixed(2),
        spaceUsage: (spaceComplexity / 1024).toFixed(2) + ' KB',
        logProbWorstCase: logProbWorstCase.toFixed(2),
        recommendation: n > 1000 ? 
            "Use AVL or Red-Black tree for guaranteed O(log n) performance" :
            "Standard BST is sufficient"
    };
}

function _logFactorial(n) {
    // Stirling's approximation: ln(n!) ≈ n ln n - n + O(log n)
    return n * Math.log(n) - n + 0.5 * Math.log(2 * Math.PI * n);
}
6. HEAP (PRIORITY QUEUE)
Explanation (10 lines)
A Heap is a complete binary tree data structure that satisfies the heap property: in a max-heap, parent nodes are greater than or equal to their children; in a min-heap, parents are smaller than children. This property enables O(log n) insertion and deletion, with O(1) access to the maximum or minimum element. Heaps are implemented efficiently using arrays where the parent-child relationship is calculated mathematically (children at indices 2i+1 and 2i+2). In social applications, heaps power recommendation engines by maintaining the highest compatibility scores, task schedulers for priority-based processing, and real-time leaderboards. Heap sort uses this structure to achieve O(n log n) sorting with O(1) additional space. Binary heaps are the foundation of priority queues used in Dijkstra's shortest path algorithm.

Code Implementation
javascript
// Max Heap implementation for priority queue
class MaxHeap {
    constructor(maxSize = 1000) {
        this.heap = [];
        this.maxSize = maxSize;
        this.size = 0;
    }

    // Get parent index - Mathematical: parent = floor((i-1)/2)
    getParentIndex(index) {
        return Math.floor((index - 1) / 2);
    }

    // Get left child index - Mathematical: left = 2i + 1
    getLeftChildIndex(index) {
        return 2 * index + 1;
    }

    // Get right child index - Mathematical: right = 2i + 2
    getRightChildIndex(index) {
        return 2 * index + 2;
    }

    // Swap elements
    swap(index1, index2) {
        [this.heap[index1], this.heap[index2]] = [this.heap[index2], this.heap[index1]];
    }

    // Insert element - O(log n)
    insert(element, priority) {
        if (this.size >= this.maxSize) {
            // Remove lowest priority element if heap is full
            if (priority <= this.heap[0].priority) return false;
            this.extractMax();
        }
        
        const item = { element, priority, timestamp: Date.now() };
        this.heap.push(item);
        this.size++;
        this.heapifyUp(this.size - 1);
        return true;
    }

    // Heapify up (bubble up) - O(log n)
    heapifyUp(index) {
        while (index > 0) {
            const parentIndex = this.getParentIndex(index);
            
            // If current priority <= parent priority, heap property satisfied
            if (this.heap[parentIndex].priority >= this.heap[index].priority) {
                break;
            }
            
            // Swap with parent
            this.swap(parentIndex, index);
            index = parentIndex;
        }
    }

    // Extract maximum element - O(log n)
    extractMax() {
        if (this.isEmpty()) return null;
        
        const max = this.heap[0];
        const last = this.heap.pop();
        this.size--;
        
        if (!this.isEmpty()) {
            this.heap[0] = last;
            this.heapifyDown(0);
        }
        
        return max.element;
    }

    // Heapify down (bubble down) - O(log n)
    heapifyDown(index) {
        let largest = index;
        const leftIndex = this.getLeftChildIndex(index);
        const rightIndex = this.getRightChildIndex(index);
        
        if (leftIndex < this.size && 
            this.heap[leftIndex].priority > this.heap[largest].priority) {
            largest = leftIndex;
        }
        
        if (rightIndex < this.size && 
            this.heap[rightIndex].priority > this.heap[largest].priority) {
            largest = rightIndex;
        }
        
        if (largest !== index) {
            this.swap(index, largest);
            this.heapifyDown(largest);
        }
    }

    // Peek at maximum without removing
    peek() {
        return this.isEmpty() ? null : this.heap[0].element;
    }

    // Check if heap is empty
    isEmpty() {
        return this.size === 0;
    }

    // Get current size
    getSize() {
        return this.size;
    }

    // Clear heap
    clear() {
        this.heap = [];
        this.size = 0;
    }

    // Build heap from array - O(n) using Floyd's algorithm
    buildHeap(array) {
        this.heap = array;
        this.size = array.length;
        
        // Start from last non-leaf node and heapify down
        for (let i = Math.floor(this.size / 2) - 1; i >= 0; i--) {
            this.heapifyDown(i);
        }
    }

    // Heap sort - O(n log n)
    heapSort() {
        const sorted = [];
        const originalHeap = [...this.heap];
        
        while (!this.isEmpty()) {
            sorted.push(this.extractMax());
        }
        
        // Restore original heap
        this.heap = originalHeap;
        this.size = originalHeap.length;
        
        return sorted;
    }
}

// Priority Queue for recommendation engine
class RecommendationPriorityQueue {
    constructor() {
        this.maxHeap = new MaxHeap(500);
        this.userHistory = new Map(); // Track shown recommendations
        this.decayFactor = 0.95; // Exponential decay factor
    }

    addRecommendation(profile, compatibilityScore, contextFactors = {}) {
        // Mathematical: Final priority calculation
        // P = (w1 * compatibility) + (w2 * novelty) + (w3 * diversity) - (w4 * recency_penalty)
        
        const weights = {
            compatibility: 0.5,
            novelty: 0.2,
            diversity: 0.15,
            recencyPenalty: 0.15
        };
        
        // Novelty score (inverse of times shown)
        const timesShown = this.userHistory.get(profile.id)?.count || 0;
        const noveltyScore = Math.exp(-timesShown / 10); // Decay after 10 views
        
        // Diversity score based on interest categories
        const diversityScore = this._calculateDiversityScore(profile);
        
        // Recency penalty (exponential decay over time)
        const lastShown = this.userHistory.get(profile.id)?.lastShown || 0;
        const hoursSinceLastShown = (Date.now() - lastShown) / (1000 * 60 * 60);
        const recencyPenalty = Math.exp(-hoursSinceLastShown / 24); // Decay over 24 hours
        
        // Apply context factors (location, time of day, etc.)
        const contextBonus = this._calculateContextBonus(contextFactors, profile);
        
        // Final priority calculation
        const priority = (weights.compatibility * compatibilityScore) +
                        (weights.novelty * noveltyScore) +
                        (weights.diversity * diversityScore) +
                        (contextBonus * 0.1) -
                        (weights.recencyPenalty * recencyPenalty);
        
        this.maxHeap.insert(profile, Math.max(0, priority));
    }

    getNextRecommendation() {
        if (this.maxHeap.isEmpty()) return null;
        
        const profile = this.maxHeap.extractMax();
        
        // Update history
        const history = this.userHistory.get(profile.id) || { count: 0, lastShown: 0 };
        history.count++;
        history.lastShown = Date.now();
        this.userHistory.set(profile.id, history);
        
        return profile;
    }

    getTopRecommendations(limit = 10) {
        const recommendations = [];
        const tempHeap = [];
        
        // Extract top K without destroying the heap
        for (let i = 0; i < limit && !this.maxHeap.isEmpty(); i++) {
            const profile = this.maxHeap.extractMax();
            recommendations.push(profile);
            tempHeap.push(profile);
        }
        
        // Restore extracted elements
        for (const profile of tempHeap) {
            // Recalculate priority (may have changed due to recency)
            const priority = this._recalculatePriority(profile);
            this.maxHeap.insert(profile, priority);
        }
        
        return recommendations;
    }

    refreshPriorities() {
        // Mathematical: Batch update of all priorities
        // Time complexity: O(n log n) where n = heap size
        
        const allItems = [];
        
        // Extract all items
        while (!this.maxHeap.isEmpty()) {
            allItems.push(this.maxHeap.extractMax());
        }
        
        // Recalculate priorities with updated factors
        for (const profile of allItems) {
            const newPriority = this._recalculatePriority(profile);
            this.maxHeap.insert(profile, newPriority);
        }
    }

    _calculateDiversityScore(profile) {
        // Mathematical: Jaccard similarity with recently shown profiles
        // Diversity = 1 - max_similarity
        
        const recentProfiles = Array.from(this.userHistory.keys())
            .slice(0, 10)
            .map(id => this._getProfileById(id))
            .filter(p => p);
        
        if (recentProfiles.length === 0) return 1;
        
        let maxSimilarity = 0;
        for (const recent of recentProfiles) {
            const similarity = this._jaccardSimilarity(
                profile.interests || [],
                recent.interests || []
            );
            maxSimilarity = Math.max(maxSimilarity, similarity);
        }
        
        return 1 - maxSimilarity;
    }

    _jaccardSimilarity(set1, set2) {
        if (set1.length === 0 && set2.length === 0) return 1;
        
        const intersection = set1.filter(x => set2.includes(x)).length;
        const union = new Set([...set1, ...set2]).size;
        
        return intersection / union;
    }

    _calculateContextBonus(contextFactors, profile) {
        let bonus = 0;
        
        // Time of day matching
        const currentHour = new Date().getHours();
        const profileActiveHour = profile.preferredActiveHour || 12;
        const hourSimilarity = 1 - Math.abs(currentHour - profileActiveHour) / 12;
        bonus += hourSimilarity * 0.3;
        
        // Location proximity
        if (contextFactors.currentLocation && profile.location) {
            const distance = this._haversineDistance(
                contextFactors.currentLocation,
                profile.location
            );
            const proximityBonus = Math.exp(-distance / 5); // Decay over 5km
            bonus += proximityBonus * 0.4;
        }
        
        // Weather/season matching (if available)
        if (contextFactors.weather && profile.preferredWeather) {
            const weatherMatch = profile.preferredWeather.includes(contextFactors.weather) ? 1 : 0;
            bonus += weatherMatch * 0.3;
        }
        
        return bonus;
    }

    _haversineDistance(loc1, loc2) {
        const R = 6371;
        const φ1 = loc1.lat * Math.PI / 180;
        const φ2 = loc2.lat * Math.PI / 180;
        const Δφ = (loc2.lat - loc1.lat) * Math.PI / 180;
        const Δλ = (loc2.lng - loc1.lng) * Math.PI / 180;
        
        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        return R * c;
    }

    _recalculatePriority(profile) {
        const compatibilityScore = profile.compatibilityScore || 50;
        return compatibilityScore * this.decayFactor;
    }

    _getProfileById(id) {
        // Placeholder - would fetch from database/cache
        return null;
    }

    getStatistics() {
        const heapSize = this.maxHeap.getSize();
        const totalShown = Array.from(this.userHistory.values())
            .reduce((sum, h) => sum + h.count, 0);
        
        // Mathematical: Diversity entropy
        const interestDistribution = this._calculateInterestDistribution();
        let entropy = 0;
        for (const count of Object.values(interestDistribution)) {
            const probability = count / totalShown;
            if (probability > 0) {
                entropy -= probability * Math.log2(probability);
            }
        }
        
        return {
            queueSize: heapSize,
            totalRecommendationsShown: totalShown,
            diversityEntropy: entropy.toFixed(4) + ' bits',
            averagePriority: this._calculateAveragePriority(),
            refreshRate: this._calculateRefreshRate()
        };
    }

    _calculateInterestDistribution() {
        const distribution = {};
        for (const [id, history] of this.userHistory) {
            const profile = this._getProfileById(id);
            if (profile && profile.interests) {
                for (const interest of profile.interests) {
                    distribution[interest] = (distribution[interest] || 0) + history.count;
                }
            }
        }
        return distribution;
    }

    _calculateAveragePriority() {
        if (this.maxHeap.isEmpty()) return 0;
        
        let sum = 0;
        for (const item of this.maxHeap.heap) {
            sum += item.priority;
        }
        return (sum / this.maxHeap.size).toFixed(2);
    }

    _calculateRefreshRate() {
        // Mathematical: Optimal refresh rate based on queue dynamics
        // Refresh rate should be proportional to change rate
        
        const queueSize = this.maxHeap.getSize();
        const changeRate = this._estimateChangeRate();
        
        // Refresh when changeRate * queueSize > threshold
        const optimalRefreshInterval = 30000 / (1 + changeRate * queueSize); // milliseconds
        
        return Math.max(5000, Math.min(60000, optimalRefreshInterval)) + ' ms';
    }

    _estimateChangeRate() {
        // Estimate how quickly priorities are changing
        const recentChanges = this._getRecentChanges();
        if (recentChanges.length < 2) return 0.1;
        
        // Linear regression on change timestamps
        let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
        for (let i = 0; i < recentChanges.length; i++) {
            sumX += i;
            sumY += recentChanges[i].magnitude;
            sumXY += i * recentChanges[i].magnitude;
            sumX2 += i * i;
        }
        
        const slope = (recentChanges.length * sumXY - sumX * sumY) / 
                      (recentChanges.length * sumX2 - sumX * sumX);
        
        return Math.abs(slope);
    }

    _getRecentChanges() {
        // Placeholder - would track priority changes
        return [];
    }
}

// Mathematical analysis of Heap operations
function analyzeHeapPerformance() {
    // Mathematical formulas for Binary Heap analysis
    
    const n = 10000; // Number of elements
    
    // Height of heap: h = ⌊log₂(n)⌋
    const height = Math.floor(Math.log2(n));
    
    // Number of nodes at level i: 2^i
    // Total nodes: Σ(2^i) for i=0 to h = 2^(h+1) - 1
    
    // Build heap complexity: O(n) using Floyd's algorithm
    // Proof: Σ(height of node) = Σ(floor(log₂(n - i))) ≈ n
    
    const buildHeapComplexity = n;
    
    // Insert complexity: O(log n) average, O(log n) worst
    const insertComplexity = Math.log2(n);
    
    // Extract max complexity: O(log n)
    const extractComplexity = Math.log2(n);
    
    // Heap sort complexity: O(n log n)
    const heapSortComplexity = n * Math.log2(n);
    
    // Array representation memory: O(n)
    const memoryUsage = n * 16; // 16 bytes per element (priority + data reference)
    
    // Probability of worst-case (already sorted) - very low for random data
    const probWorstCase = 1 / Math.pow(2, n); // Extremely small
    
    return {
        height: height,
        buildHeapComplexity: `O(${buildHeapComplexity}) = ${buildHeapComplexity} operations`,
        insertComplexity: `O(log n) ≈ ${insertComplexity.toFixed(2)} operations`,
        extractComplexity: `O(log n) ≈ ${extractComplexity.toFixed(2)} operations`,
        heapSortComplexity: `O(n log n) ≈ ${heapSortComplexity.toFixed(0)} operations`,
        memoryUsage: (memoryUsage / 1024).toFixed(2) + ' KB',
        probWorstCase: probWorstCase.toExponential(2),
        averageCaseEfficiency: ((buildHeapComplexity / (n * Math.log2(n))) * 100).toFixed(2) + '%'
    };
}
7. GRAPH
Explanation (10 lines)
A Graph is a non-linear data structure consisting of vertices (nodes) connected by edges, representing relationships between entities. Graphs can be directed (one-way relationships) or undirected (mutual relationships), weighted (edges have costs) or unweighted. Social networks are naturally modeled as graphs where users are vertices and friendships or interactions are edges. Graph traversal algorithms include Depth-First Search (DFS) using stacks and Breadth-First Search (BFS) using queues for exploring connections. The adjacency list representation stores edges per vertex, offering O(V+E) space complexity with efficient iteration over neighbors. In dating applications, graphs model user connections, find mutual friends, suggest potential matches through shared connections, and detect communities of similar users. Path-finding algorithms like Dijkstra's find shortest connection paths between users through mutual acquaintances.

Code Implementation
javascript
// Graph implementation for social network
class SocialGraph {
    constructor() {
        this.adjacencyList = new Map(); // Vertex -> Array of {node, weight, timestamp}
        this.vertexData = new Map();    // Vertex -> User data
        this.edgeCount = 0;
    }

    // Add vertex (user) to graph
    addVertex(userId, userData = {}) {
        if (!this.adjacencyList.has(userId)) {
            this.adjacencyList.set(userId, []);
            this.vertexData.set(userId, userData);
            return true;
        }
        return false;
    }

    // Add edge (connection) between users
    addEdge(user1Id, user2Id, weight = 1, type = 'friend') {
        if (!this.adjacencyList.has(user1Id)) this.addVertex(user1Id);
        if (!this.adjacencyList.has(user2Id)) this.addVertex(user2Id);
        
        const edge = {
            node: user2Id,
            weight: weight,
            type: type,
            timestamp: Date.now()
        };
        
