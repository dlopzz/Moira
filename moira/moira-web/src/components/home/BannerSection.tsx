import Image from 'next/image';
import Link from 'next/link';
import { imageUrl, type HomeSectionBanner } from '@/lib/api';

type Props = { settings: HomeSectionBanner['settings'] };

export default function BannerSection({ settings }: Props) {
  const { image, title, subtitle, button_link } = settings;
  const resolvedImage = imageUrl(image);

  if (!resolvedImage && !title && !subtitle) return null;

  const inner = (
    <>
      {resolvedImage && (
        <Image
          src={resolvedImage}
          alt={title ?? 'Banner'}
          fill
          className="home-banner__img"
          sizes="100vw"
        />
      )}
      <div className="home-banner__overlay" />

      {(title || subtitle) && (
        <div className="home-banner__content site-container">
          {title && <h3 className="home-banner__title">{title}</h3>}
          {subtitle && <p className="home-banner__subtitle">{subtitle}</p>}
        </div>
      )}
    </>
  );

  if (button_link) {
    return (
      <Link href={button_link} className="home-banner">
        {inner}
      </Link>
    );
  }

  return <div className="home-banner">{inner}</div>;
}
