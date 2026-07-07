'use client';

import { createFetchOnceContext } from './fetch-once-context';
import { api, type Category } from './api';

export const [CategoriesProvider, useCategories] = createFetchOnceContext<Category[]>(
  api.getCategories,
  { debugName: 'Categories' }
);
