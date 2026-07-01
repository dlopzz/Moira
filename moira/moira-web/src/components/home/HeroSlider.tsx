'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { imageUrl, type HeroSlide } from '@/lib/api';

const DURATION = 800;

type Props = { slides: HeroSlide[] };

export default function HeroSlider({ slides }: Props) {
  const [current, setCurrent] = useState(0);
  const [exiting, setExiting] = useState<number | null>(null);
  const [direction, setDirection] = useState<'next' | 'prev'>('next');
  const locked = useRef(false);

  const navigate = useCallback((nextIdx: number, dir: 'next' | 'prev') => {
    if (locked.current || nextIdx === current) return;
    locked.current = true;
    setDirection(dir);
    setExiting(current);
    setCurrent(nextIdx);
    setTimeout(() => {
      setExiting(null);
      locked.current = false;
    }, DURATION);
  }, [current]);

  const prev = useCallback(
    () => navigate(current === 0 ? slides.length - 1 : current - 1, 'prev'),
    [current, slides.length, navigate]
  );

  const next = useCallback(
    () => navigate(current === slides.length - 1 ? 0 : current + 1, 'next'),
    [current, slides.length, navigate]
  );

  useEffect(() => {
    if (slides.length <= 1) return;
    const id = setInterval(next, 5000);
    return () => clearInterval(id);
  }, [next, slides.length]);

  if (!slides.length) return null;

  function getClassName(i: number): string {
    const effect = slides[i].transition ?? 'fade';
    const rev = direction === 'prev' ? '-rev' : '';
    if (i === exiting) return `hero-slider__slide hero-slider__slide--${effect}-exit${rev}`;
    if (i === current && exiting !== null) return `hero-slider__slide hero-slider__slide--${effect}-enter${rev}`;
    if (i === current) return 'hero-slider__slide hero-slider__slide--active';
    return 'hero-slider__slide';
  }

  return (
    <div className="hero-slider">
      {slides.map((slide, i) => (
        <div key={i} className={getClassName(i)} aria-hidden={i !== current}>
          {imageUrl(slide.image) && (
            <Image
              src={imageUrl(slide.image)!}
              alt={slide.title ?? ''}
              fill
              className="hero-slider__img"
              sizes="100vw"
              priority={i === 0}
            />
          )}
          <div className="hero-slider__overlay" />
          {(slide.title || slide.subtitle || slide.button_text) && (
            <div className="hero-slider__content site-container">
              {slide.title && <h2 className="hero-slider__title">{slide.title}</h2>}
              {slide.subtitle && <p className="hero-slider__subtitle">{slide.subtitle}</p>}
              {slide.button_text && slide.button_link && (
                <Link href={slide.button_link} className="button alt hero-slider__btn">
                  {slide.button_text}
                </Link>
              )}
            </div>
          )}
        </div>
      ))}

      {slides.length > 1 && (
        <>
          <button className="hero-slider__arrow hero-slider__arrow--prev" onClick={prev} aria-label="Slide anterior">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <polyline points="15 18 9 12 15 6" />
            </svg>
          </button>
          <button className="hero-slider__arrow hero-slider__arrow--next" onClick={next} aria-label="Siguiente slide">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <polyline points="9 18 15 12 9 6" />
            </svg>
          </button>
          <div className="hero-slider__dots">
            {slides.map((_, i) => (
              <button
                key={i}
                className={`hero-slider__dot${i === current ? ' hero-slider__dot--active' : ''}`}
                onClick={() => navigate(i, i > current ? 'next' : 'prev')}
                aria-label={`Ir al slide ${i + 1}`}
              />
            ))}
          </div>
        </>
      )}
    </div>
  );
}
