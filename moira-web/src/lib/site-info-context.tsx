'use client';

import { createFetchOnceContext } from './fetch-once-context';
import { api, type SiteInfo } from './api';

export const [SiteInfoProvider, useSiteInfo] = createFetchOnceContext<SiteInfo>(
  api.getSiteSettings,
  { debugName: 'SiteInfo' }
);
