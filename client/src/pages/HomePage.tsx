import Hero from "../components/Hero";
import NewsSlider from "../components/NewsSlider";
import ServicesCards from "../components/ServicesCards";

interface HomePageProps {
  onNavigate: (page: string) => void;
}

export default function HomePage({ onNavigate }: HomePageProps) {
  return (
    <div className="animate-fadeIn">
      <Hero onNavigate={onNavigate} />
      <NewsSlider />
      <ServicesCards onNavigate={onNavigate} />
    </div>
  );
}
