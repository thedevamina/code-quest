import { useCallback, useEffect, useState } from 'react';
import { api } from '@/services/api';

export type Completion = { course_id: string; chapter_id: string };
export type TestResult = {
  id: string;
  course_id: string;
  chapter_id: string;
  score: number;
  correct_answers: number;
  incorrect_answers: number;
  time_taken: string;
  created_at: string;
};
export type CourseProgress = { course_id: string; total_chapters: number; completed_chapters: number; progress: number };

export function useUserData(userId: string | null) {
  const [completions, setCompletions] = useState<string[]>([]);
  const [results, setResults] = useState<TestResult[]>([]);
  const [courseProgress, setCourseProgress] = useState<Record<string, number>>({});
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    if (!userId) {
      setCompletions([]);
      setResults([]);
      setCourseProgress({});
      setLoading(false);
      return;
    }
    try { const data = await api.progress(); setCompletions(data.completions); setResults(data.results as TestResult[]); setCourseProgress(Object.fromEntries(data.course_progress.map((course) => [course.course_id, course.progress]))); } catch { setCompletions([]); setResults([]); setCourseProgress({}); }
    setLoading(false);
  }, [userId]);

  useEffect(() => { load(); }, [load]);

  const toggleCompletion = useCallback(async (chapterId: string, courseId: string) => {
    if (!userId) return;
    const data = await api.toggleCompletion(String(Number(chapterId.split('-').pop())), courseId === 'oop' ? '1' : courseId === 'dsa' ? '2' : '3');
    setCompletions(data.completions);
    setCourseProgress(Object.fromEntries(data.course_progress.map((course) => [course.course_id, course.progress])));
  }, [userId]);

  const saveTestResult = useCallback(async (result: Omit<TestResult, 'id' | 'created_at'>) => {
    if (!userId) return;
    const response = await api.saveTestResult(result);
    setResults((prev) => [response.result, ...prev]);
  }, [userId]);

  return { completions, results, courseProgress, loading, toggleCompletion, saveTestResult, reload: load };
}
