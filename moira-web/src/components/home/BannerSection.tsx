import Image from 'next/image';
import Link from 'next/link';
import { imageUrl, type HomeSectionBanner } from '@/lib/api';

type Props = { settings: HomeSectionBanner['settings'] };

export default function BannerSection({ settings }: Props) {
  const { image, title, subtitle, text_align, text_valign, link } = settings;
  const resolvedImage = imageUrl(image);

  if (!resolvedImage && !title && !subtitle) return null;

  const align = text_align ?? 'center';
  const valign = text_valign ?? 'center';
  const alignItems = align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center';
  const justifyContent = valign === 'top' ? 'flex-start' : valign === 'bottom' ? 'flex-end' : 'center';

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
        <div
          className="home-banner__content site-container"
          style={{ textAlign: align, alignItems, justifyContent }}
        >
          {title && <h3 className="home-banner__title">{title}</h3>}
          {subtitle && <p className="home-banner__subtitle">{subtitle}</p>}
        </div>
      )}
    </>
  );

  if (link) {
    return (
      <Link href={link} className="home-banner">
        {inner}
      </Link>
    );
  }

  return <div className="home-banner">{inner}</div>;
}
