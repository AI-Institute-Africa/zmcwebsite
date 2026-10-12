import { useState, useEffect } from "react";
import { Camera, ChevronLeft, ChevronRight, ZoomIn, Calendar, MapPin, Video, Play, Loader2, X } from "lucide-react";
import PageHero from "../components/PageHero";

interface PhotosPageProps {
  onNavigate: (page: string) => void;
}

interface GalleryPhoto {
  id: string;
  url: string;
  caption?: string;
}

interface GalleryAlbum {
  id: string;
  title: string;
  date?: string;
  location?: string;
  cover?: string;
  photos: GalleryPhoto[];
}

interface VideoRecord {
  id: string;
  title: string;
  url: string;
  thumbnail?: string;
  date?: string;
  duration?: string;
}

export default function PhotosPage({ onNavigate }: PhotosPageProps) {
  const [albums, setAlbums] = useState<GalleryAlbum[]>([]);
  const [videos, setVideos] = useState<VideoRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedAlbum, setSelectedAlbum] = useState<string | null>(null);
  const [activePhoto, setActivePhoto] = useState(0);
  const [playingVideo, setPlayingVideo] = useState<VideoRecord | null>(null);

  useEffect(() => {
    setIsLoading(false);
  }, []);

  const activeAlbum = albums.find((a) => a.id === selectedAlbum);

  const navigatePhoto = (direction: number) => {
    if (!activeAlbum) return;
    setActivePhoto((prev) => (prev + direction + activeAlbum.photos.length) % activeAlbum.photos.length);
  };

  const currentPhoto = activeAlbum ? activeAlbum.photos[activePhoto] : null;

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title="Gallery"
        subtitle="Browse photos and videos from ZMC events, workshops, and activities"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Media Centre" }, { label: "Gallery" }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          {isLoading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading media...</p>
            </div>
          ) : albums.length === 0 && videos.length === 0 ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <Camera className="w-16 h-16 mx-auto mb-4" style={{ color: "var(--neutral-200)" }} />
              <h3 className="text-xl font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>No media available yet</h3>
              <p style={{ color: "var(--neutral-500)" }}>Photos and videos will appear here once they are published.</p>
            </div>
          ) : !selectedAlbum ? (
            <>
              <div className="text-center mb-10 md:mb-12">
                <Camera className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
                <h2 className="text-xl md:text-2xl mb-3" style={{ color: "var(--primary-dark)" }}>Photo Albums</h2>
                <p className="text-sm md:text-base max-w-[600px] mx-auto" style={{ color: "var(--neutral-600)" }}>
                  Explore our collection of photos from various ZMC events and activities across Zimbabwe.
                </p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6 mb-16">
                {albums.map((album) => (
                  <button
                    key={album.id}
                    onClick={() => {
                      setSelectedAlbum(album.id);
                      setActivePhoto(0);
                    }}
                    className="text-left rounded-2xl overflow-hidden transition-all hover:-translate-y-1 cursor-pointer border-none p-0 bg-white group"
                    style={{ boxShadow: "var(--shadow-md)", border: "1px solid var(--neutral-100)" }}
                    data-testid={`album-card-${album.id}`}
                  >
                    <div className="h-48 md:h-56 relative overflow-hidden bg-neutral-200">
                      <img
                        src={album.cover || album.photos?.[0]?.url}
                        alt={album.title}
                        loading="lazy"
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                      />
                      <div className="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-2.5 py-1 rounded-full">
                        {album.photos.length} photos
                      </div>
                      <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center">
                        <ZoomIn className="w-8 h-8 text-white opacity-0 group-hover:opacity-90 transition-opacity" />
                      </div>
                    </div>
                    <div className="p-4 md:p-5">
                      <h3 className="text-base md:text-lg font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>
                        {album.title}
                      </h3>
                      <div className="flex items-center gap-2 text-xs md:text-sm mb-1" style={{ color: "var(--neutral-500)" }}>
                        <Calendar className="w-3.5 h-3.5" />
                        {album.date}
                      </div>
                      <div className="flex items-center gap-2 text-xs md:text-sm" style={{ color: "var(--neutral-500)" }}>
                        <MapPin className="w-3.5 h-3.5" />
                        {album.location}
                      </div>
                    </div>
                  </button>
                ))}
              </div>

              {/* Videos Section */}
              <div className="text-center mb-10 md:mb-12">
                <Video className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
                <h2 className="text-xl md:text-2xl mb-3" style={{ color: "var(--primary-dark)" }}>Videos</h2>
                <p className="text-sm md:text-base max-w-[600px] mx-auto" style={{ color: "var(--neutral-600)" }}>
                  Watch highlights from ZMC events, training workshops, and stakeholder engagements.
                </p>
              </div>

              {playingVideo && (
                <div
                  className="mb-8 rounded-2xl overflow-hidden"
                  style={{ boxShadow: "var(--shadow-md)", border: "1px solid var(--neutral-100)" }}
                  data-testid="inline-video-player"
                >
                  <div className="aspect-video bg-black">
                    <video
                      key={playingVideo.id}
                      src={playingVideo.url}
                      poster={playingVideo.thumbnail}
                      controls
                      autoPlay
                      playsInline
                      className="w-full h-full"
                    />
                  </div>
                  <div className="p-4 md:p-5 bg-white flex items-start justify-between gap-4">
                    <div>
                      <h3 className="text-base md:text-lg font-semibold mb-1" style={{ color: "var(--neutral-800)" }}>
                        {playingVideo.title}
                      </h3>
                      <p className="text-sm m-0" style={{ color: "var(--neutral-500)" }}>
                        {playingVideo.date} · {playingVideo.duration}
                      </p>
                    </div>
                    <button
                      onClick={() => setPlayingVideo(null)}
                      className="flex items-center gap-1 text-sm font-semibold bg-transparent border-none cursor-pointer"
                      style={{ color: "var(--primary)" }}
                      data-testid="button-close-video"
                    >
                      <X className="w-4 h-4" /> Close
                    </button>
                  </div>
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                {videos.map((video) => (
                  <button
                    key={video.id}
                    onClick={() => {
                      setPlayingVideo(video);
                      window.scrollTo({ top: document.getElementById("inline-video-player")?.offsetTop || 0, behavior: "smooth" });
                    }}
                    className="text-left rounded-2xl overflow-hidden transition-all hover:-translate-y-1 cursor-pointer border-none p-0 bg-white group"
                    style={{
                      boxShadow: "var(--shadow-md)",
                      border: playingVideo?.id === video.id ? "2px solid var(--primary)" : "1px solid var(--neutral-100)",
                    }}
                    data-testid={`video-card-${video.id}`}
                  >
                    <div className="h-48 md:h-56 relative overflow-hidden bg-neutral-200">
                      <img
                        src={video.thumbnail}
                        alt={video.title}
                        loading="lazy"
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                      />
                      <div className="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/35 transition-all">
                        <div className="w-16 h-16 rounded-full bg-white/25 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/40 group-hover:scale-110 transition-all">
                          <Play className="w-8 h-8 text-white ml-1" fill="white" />
                        </div>
                      </div>
                      <div className="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-2.5 py-1 rounded-full">
                        {video.duration}
                      </div>
                    </div>
                    <div className="p-4 md:p-5">
                      <h3 className="text-base md:text-lg font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>
                        {video.title}
                      </h3>
                      <div className="flex items-center gap-2 text-xs md:text-sm" style={{ color: "var(--neutral-500)" }}>
                        <Calendar className="w-3.5 h-3.5" />
                        {video.date}
                      </div>
                    </div>
                  </button>
                ))}
              </div>
            </>
          ) : activeAlbum ? (
            <>
              <div className="mb-8">
                <button
                  onClick={() => setSelectedAlbum(null)}
                  className="flex items-center gap-2 text-sm font-medium mb-6 bg-transparent border-none cursor-pointer transition-colors hover:opacity-80 p-0"
                  style={{ color: "var(--primary)" }}
                  data-testid="button-back-to-albums"
                >
                  <ChevronLeft className="w-4 h-4" />
                  Back to Albums
                </button>
                <h2 className="text-xl md:text-2xl mb-2" style={{ color: "var(--primary-dark)" }} data-testid="text-album-title">
                  {activeAlbum.title}
                </h2>
                <div className="flex items-center gap-4 flex-wrap text-sm" style={{ color: "var(--neutral-500)" }}>
                  <span className="flex items-center gap-1.5">
                    <Calendar className="w-4 h-4" />
                    {activeAlbum.date}
                  </span>
                  <span className="flex items-center gap-1.5">
                    <MapPin className="w-4 h-4" />
                    {activeAlbum.location}
                  </span>
                  <span className="flex items-center gap-1.5">
                    <Camera className="w-4 h-4" />
                    {activeAlbum.photos.length} photos
                  </span>
                </div>
              </div>

              {currentPhoto && (
                <div
                  className="rounded-2xl overflow-hidden mb-6 bg-neutral-900 relative"
                  style={{ boxShadow: "var(--shadow-md)" }}
                  data-testid="inline-photo-viewer"
                >
                  <div className="aspect-[16/10] md:aspect-[16/9] flex items-center justify-center">
                    <img src={currentPhoto.url} alt={currentPhoto.caption} className="w-full h-full object-contain" />
                  </div>
                  <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/75 to-transparent p-4 md:p-6">
                    <p className="text-white text-sm md:text-base m-0">{currentPhoto.caption}</p>
                    <p className="text-white/60 text-xs md:text-sm mt-1 mb-0">
                      Photo {activePhoto + 1} of {activeAlbum.photos.length}
                    </p>
                  </div>
                  <button
                    onClick={() => navigatePhoto(-1)}
                    className="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center text-white bg-black/50 hover:bg-black/70 border-none cursor-pointer transition-colors"
                    aria-label="Previous photo"
                    data-testid="button-lightbox-prev"
                  >
                    <ChevronLeft className="w-6 h-6" />
                  </button>
                  <button
                    onClick={() => navigatePhoto(1)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center text-white bg-black/50 hover:bg-black/70 border-none cursor-pointer transition-colors"
                    aria-label="Next photo"
                    data-testid="button-lightbox-next"
                  >
                    <ChevronRight className="w-6 h-6" />
                  </button>
                </div>
              )}

              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
                {activeAlbum.photos.map((photo, index) => (
                  <button
                    key={photo.id}
                    onClick={() => setActivePhoto(index)}
                    className="aspect-square rounded-xl overflow-hidden cursor-pointer relative group border-none p-0 bg-neutral-200"
                    style={{ outline: index === activePhoto ? "3px solid var(--primary)" : "none" }}
                    data-testid={`photo-${photo.id}`}
                  >
                    <img src={photo.url} alt={photo.caption} loading="lazy" className="w-full h-full object-cover" />
                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-all" />
                  </button>
                ))}
              </div>
            </>
          ) : null}
        </div>
      </div>
    </div>
  );
}
