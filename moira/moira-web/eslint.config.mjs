import { defineConfig, globalIgnores } from "eslint/config";
import nextVitals from "eslint-config-next/core-web-vitals";
import nextTs from "eslint-config-next/typescript";

// Endpoints that must be fetched exactly once, through a shared context, not
// independently by every component that needs them. Each one was independently
// re-fetched by multiple co-mounted components (Header/Footer/CookieNotice/the
// recaptcha hook for "settings"; Header/Footer for "categories") across several
// review rounds before being consolidated — this rule fails CI on a future
// instance instead of relying on a review round to notice it by eye.
//
// To ban a new endpoint: add an entry below and add its provider file to
// `providerFiles`. NOTE: flat config does not merge `rules` across config
// objects that match the same file — a later object's `no-restricted-syntax`
// silently REPLACES an earlier one's, it doesn't add to it. All selectors
// must stay combined into the single array below, in one config object.
const sharedFetchEndpoints = [
  { method: "getSiteSettings", hook: "useSiteInfo() from '@/lib/site-info-context'" },
  { method: "getCategories", hook: "useCategories() from '@/lib/categories-context'" },
];

const providerFiles = [
  "src/lib/site-info-context.tsx",
  "src/lib/categories-context.tsx",
];

const noRestrictedSyntaxRules = sharedFetchEndpoints.flatMap(({ method, hook }) => [
  {
    selector: `CallExpression[callee.object.name='api'][callee.property.name='${method}']`,
    message: `Don't call api.${method}() directly — use ${hook} so this stays fetched once and shared across the app.`,
  },
  {
    selector: `CallExpression[callee.object.name='api'][callee.computed=true][callee.property.value='${method}']`,
    message: `Don't call api['${method}']() directly — use ${hook} so this stays fetched once and shared across the app.`,
  },
  {
    selector: `VariableDeclarator[init.name='api'] > ObjectPattern > Property[key.name='${method}']`,
    message: `Don't destructure ${method} off api — use ${hook} so this stays fetched once and shared across the app.`,
  },
]);

const eslintConfig = defineConfig([
  ...nextVitals,
  ...nextTs,
  // Override default ignores of eslint-config-next.
  globalIgnores([
    // Default ignores of eslint-config-next:
    ".next/**",
    "out/**",
    "build/**",
    "next-env.d.ts",
  ]),
  {
    files: ["src/**/*.{ts,tsx}"],
    ignores: providerFiles,
    rules: {
      "no-restricted-syntax": ["error", ...noRestrictedSyntaxRules],
    },
  },
]);

export default eslintConfig;
