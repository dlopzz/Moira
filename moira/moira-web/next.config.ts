import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'moura.test',
        pathname: '/storage/**',
      },
    ],
  },
};

export default nextConfig;
