import Header from '@/components/Header';
import HeroSlider from '@/components/home/HeroSlider';
import ProductTabsSection from '@/components/home/ProductTabsSection';
import BannerSection from '@/components/home/BannerSection';
import { api, type HomeSection, type HomeSectionBanner, type HomeSectionHeroSlider, type HomeSectionProductTabs } from '@/lib/api';

export const dynamic = 'force-dynamic';

export default async function HomePage() {
  let sections: HomeSection[] = [];
  try {
    const res = await api.getHome();
    sections = res.data;
  } catch {
    sections = [];
  }

  return (
    <>
      <Header />

      {sections.map(s => {
        if (s.type === 'hero_slider') {
          const section = s as HomeSectionHeroSlider;
          return <HeroSlider key={s.id} slides={section.settings.slides} />;
        }
        if (s.type === 'banner') {
          const section = s as HomeSectionBanner;
          return <BannerSection key={s.id} settings={section.settings} />;
        }
        if (s.type === 'product_tabs') {
          const section = s as HomeSectionProductTabs;
          return <ProductTabsSection key={s.id} title={section.title} tabs={section.settings.tabs} />;
        }
        return null;
      })}
    </>
  );
}
