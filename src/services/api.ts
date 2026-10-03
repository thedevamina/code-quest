const API_URL = import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api';

let token = localStorage.getItem('codequest_token');

export type ApiUser = { id: string; email: string; name: string; role: string; profile: { id: number; user_id: number; full_name: string; learning_goal: string } | null };
export type ApiTestResult = { id: string; course_id: string; chapter_id: string; score: number; correct_answers: number; incorrect_answers: number; time_taken: string; created_at: string };
export type ApiCourseProgress = { course_id: string; total_chapters: number; completed_chapters: number; progress: number };
export type ApiQuestion = { id: number; question: string; options: string[] };
export type ApiAssessment = { id: number; type: 'chapter' | 'midterm' | 'final'; title: string; questions: ApiQuestion[] };
export type ApiLesson = {
  chapterNumber: string;
  title: string;
  lead: string;
  objective: string;
  readTime: string;
  sections: { heading: string; body: string; code?: { file: string; lines: string } }[];
  concepts: { number: string; title: string; text: string }[];
  note: string;
};

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');
  if (options.body) headers.set('Content-Type', 'application/json');
  if (token) headers.set('Authorization', `Bearer ${token}`);
  const response = await fetch(`${API_URL}${path}`, { ...options, headers });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(payload.message ?? Object.values(payload.errors ?? {}).flat().join(' ') ?? 'Request failed');
  return payload as T;
}

export const api = {
  async register(name: string, email: string, password: string) { const result = await request<{ user: ApiUser; token: string }>('/auth/register', { method: 'POST', body: JSON.stringify({ name, email, password }) }); token = result.token; localStorage.setItem('codequest_token', token); return result.user; },
  async login(email: string, password: string) { const result = await request<{ user: ApiUser; token: string }>('/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }); token = result.token; localStorage.setItem('codequest_token', token); return result.user; },
  async logout() { await request('/auth/logout', { method: 'POST' }); token = null; localStorage.removeItem('codequest_token'); },
  async me() { return (await request<{ user: ApiUser }>('/auth/me')).user; },
  async updateProfile(fullName: string, learningGoal: string) { return (await request<{ profile: ApiUser['profile'] }>('/profile', { method: 'PUT', body: JSON.stringify({ full_name: fullName, learning_goal: learningGoal }) })).profile; },
  async progress() { return request<{ completions: string[]; course_progress: ApiCourseProgress[]; results: ApiTestResult[]; exams: unknown[] }>('/progress'); },
  async courses() { return request<{ courses: { slug: string; title: string; description: string; code: string; level: string; chapters: { id: number; slug: string; number: number; title: string; description: string; status: string }[] }[] }>('/courses'); },
  async toggleCompletion(chapterId: string, courseId: string) { return request<{ completions: string[]; course_progress: ApiCourseProgress[]; results: ApiTestResult[]; exams: unknown[] }>(`/chapters/${chapterId}/completion`, { method: 'POST', body: JSON.stringify({ course_id: courseId }) }); },
  async saveTestResult(result: Omit<ApiTestResult, 'id' | 'created_at'>) { return request<{ result: ApiTestResult }>('/results', { method: 'POST', body: JSON.stringify(result) }); },
  async assessment(course: string, type: 'chapter' | 'midterm' | 'final', chapter?: string) { return (await request<{ assessment: ApiAssessment }>(`/assessments/${course}/${type}${chapter ? `/${chapter}` : ''}`)).assessment; },
  async material(chapter: string) { return (await request<{ materials: { content: ApiLesson }[] }>(`/chapters/${chapter}/material`)).materials[0]?.content; },
  async submitAssessment(course: string, type: 'chapter' | 'midterm' | 'final', answers: number[], timeTaken: string, chapter?: string) { return (await request<{ result: ApiTestResult & { attempt_id: number } }>('/assessments/submit', { method: 'POST', body: JSON.stringify({ course, type, chapter, answers, time_taken: timeTaken }) })).result; },
  async result(id: number) { return (await request<{ result: ApiTestResult & { assessment_type: 'chapter' | 'midterm' | 'final'; review_data: { answers: (number | null)[]; questions: (ApiQuestion & { correctIndex: number })[] } } }>(`/results/${id}`)).result; },
  async adminCreateTest(data: Record<string, unknown>) { return request('/admin/tests', { method: 'POST', body: JSON.stringify(data) }); },
  async adminCreateCourse(data: Record<string, unknown>) { return request('/admin/courses', { method: 'POST', body: JSON.stringify(data) }); },
  async adminCourses() { return request<{ courses: { id: number; slug: string; title: string; description: string }[] }>('/admin/courses'); },
  async adminChapters(courseId?: number) { return request<{ chapters: { id: number; course_id: number; number: number; title: string; description: string }[] }>(`/admin/chapters${courseId ? `?course_id=${courseId}` : ''}`); },
  async adminMaterials(chapterId?: number) { return request<{ materials: { id: number; chapter_id: number; title: string; content: unknown }[] }>(`/admin/materials${chapterId ? `?chapter_id=${chapterId}` : ''}`); },
  async adminTests(courseId?: number) { return request<{ tests: { id: number; course_id: number; chapter_id: number; title: string }[] }>(`/admin/tests${courseId ? `?course_id=${courseId}` : ''}`); },
  async adminQuestions(testId?: number) { return request<{ questions: { id: number; test_id: number; question: string; options: { id: number; option: string; is_correct: boolean; sort_order: number }[] }[] }>(`/admin/questions${testId ? `?test_id=${testId}` : ''}`); },
  async adminExams(courseId?: number) { return request<{ exams: { id: number; course_id: number; type: string; title: string }[] }>(`/admin/exams${courseId ? `?course_id=${courseId}` : ''}`); },
  async adminExamQuestions(examId?: number) { return request<{ questions: { id: number; exam_id: number; question: string; options: string[]; correct_index: number }[] }>(`/admin/exam-questions${examId ? `?exam_id=${examId}` : ''}`); },
  async adminUpdateCourse(id: number, data: Record<string, unknown>) { return request(`/admin/courses/${id}`, { method: 'PUT', body: JSON.stringify(data) }); },
  async adminDeleteCourse(id: number) { return request(`/admin/courses/${id}`, { method: 'DELETE' }); },
  async adminUpdateChapter(id: number, data: Record<string, unknown>) { return request(`/admin/chapters/${id}`, { method: 'PUT', body: JSON.stringify(data) }); },
  async adminDeleteChapter(id: number) { return request(`/admin/chapters/${id}`, { method: 'DELETE' }); },
  async adminUpdateMaterial(id: number, data: Record<string, unknown>) { return request(`/admin/materials/${id}`, { method: 'PUT', body: JSON.stringify(data) }); },
  async adminDeleteMaterial(id: number) { return request(`/admin/materials/${id}`, { method: 'DELETE' }); },
  async adminCreateChapter(data: Record<string, unknown>) { return request('/admin/chapters', { method: 'POST', body: JSON.stringify(data) }); },
  async adminCreateMaterial(data: Record<string, unknown>) { return request('/admin/materials', { method: 'POST', body: JSON.stringify(data) }); },
  async adminCreateQuestion(data: Record<string, unknown>) { return request('/admin/questions', { method: 'POST', body: JSON.stringify(data) }); },
  async adminDeleteQuestion(id: number) { return request(`/admin/questions/${id}`, { method: 'DELETE' }); },
  async adminUpdateQuestion(id: number, data: Record<string, unknown>) { return request(`/admin/questions/${id}`, { method: 'PUT', body: JSON.stringify(data) }); },
  async adminCreateExamQuestion(data: Record<string, unknown>) { return request('/admin/exam-questions', { method: 'POST', body: JSON.stringify(data) }); },
  async adminDeleteExamQuestion(id: number) { return request(`/admin/exam-questions/${id}`, { method: 'DELETE' }); },
  async adminUpdateExamQuestion(id: number, data: Record<string, unknown>) { return request(`/admin/exam-questions/${id}`, { method: 'PUT', body: JSON.stringify(data) }); },
};
