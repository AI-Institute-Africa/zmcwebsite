import { useState } from "react";
import { Camera, X, ChevronLeft, ChevronRight, ZoomIn, Calendar, MapPin, Video, Play } from "lucide-react";
import PageHero from "../components/PageHero";

interface PhotosPageProps {
  onNavigate: (page: string) => void;
}

const photoAlbums = [
  {
    id: "accreditation-2024",
    title: "Accreditation Ceremony 2024",
    date: "November 2024",
    location: "Harare International Conference Centre",
    cover: 0,
    photos: [
      { id: 1, caption: "Opening remarks by the Chairperson", color: "from-green-800 to-green-600" },
      { id: 2, caption: "Media practitioners receiving certificates", color: "from-amber-700 to-amber-500" },
      { id: 3, caption: "Group photo of newly accredited journalists", color: "from-emerald-700 to-emerald-500" },
      { id: 4, caption: "Panel discussion on media ethics", color: "from-teal-700 to-teal-500" },
      { id: 5, caption: "Keynote address", color: "from-green-700 to-green-500" },
      { id: 6, caption: "Networking session", color: "from-lime-700 to-lime-500" },
    ],
  },
  {
    id: "world-press-freedom-2024",
    title: "World Press Freedom Day 2024",
    date: "May 2024",
    location: "Rainbow Towers, Harare",
    cover: 0,
    photos: [
      { id: 7, caption: "Commemorative event opening", color: "from-blue-800 to-blue-600" },
      { id: 8, caption: "Panel on press freedom challenges", color: "from-indigo-700 to-indigo-500" },
      { id: 9, caption: "Awards presentation", color: "from-purple-700 to-purple-500" },
      { id: 10, caption: "Media stakeholders roundtable", color: "from-violet-700 to-violet-500" },
    ],
  },
  {
    id: "training-bulawayo-2024",
    title: "Media Training Workshop - Bulawayo",
    date: "August 2024",
    location: "Bulawayo Media Centre",
    cover: 0,
    photos: [
      { id: 11, caption: "Workshop participants in session", color: "from-orange-700 to-orange-500" },
      { id: 12, caption: "Practical training exercises", color: "from-red-700 to-red-500" },
      { id: 13, caption: "Certificate presentation", color: "from-rose-700 to-rose-500" },
      { id: 14, caption: "Group photo", color: "from-pink-700 to-pink-500" },
      { id: 15, caption: "Interactive Q&A session", color: "from-fuchsia-700 to-fuchsia-500" },
    ],
  },
  {
    id: "stakeholder-engagement-2024",
    title: "Stakeholder Engagement Forum",
    date: "March 2024",
    location: "Meikles Hotel, Harare",
    cover: 0,
    photos: [
      { id: 16, caption: "Forum opening address", color: "from-cyan-700 to-cyan-500" },
      { id: 17, caption: "Stakeholder presentations", color: "from-sky-700 to-sky-500" },
      { id: 18, caption: "Breakaway sessions", color: "from-teal-800 to-teal-600" },
    ],
  },
  {
    id: "community-outreach-2024",
    title: "Community Media Outreach",
    date: "June 2024",
    location: "Various Locations",
    cover: 0,
    photos: [
      { id: 19, caption: "Community engagement in Masvingo", color: "from-emerald-800 to-emerald-600" },
      { id: 20, caption: "Rural media awareness campaign", color: "from-green-900 to-green-700" },
      { id: 21, caption: "Youth media literacy programme", color: "from-lime-800 to-lime-600" },
      { id: 22, caption: "Community radio station visit", color: "from-yellow-700 to-yellow-500" },
      { id: 23, caption: "Media rights awareness session", color: "from-amber-800 to-amber-600" },
      { id: 24, caption: "Closing ceremony", color: "from-orange-800 to-orange-600" },
    ],
  },
];

const videos = [
  {
    id: "v1",
    title: "World Press Freedom Day 2024 — Highlights",
    date: "May 2024",
    duration: "4:32",
    youtubeId: "dQw4w9WgXcQ",
    color: "from-blue-800 to-blue-600",
  },
  {
    id: "v2",
    title: "Accreditation Ceremony 2024",
    date: "November 2024",
    duration: "6:15",
    youtubeId: "dQw4w9WgXcQ",
    color: "from-green-800 to-green-600",
  },
  {
    id: "v3",
    title: "Media Training Workshop — Bulawayo",
    date: "August 2024",
    duration: "3:48",
    youtubeId: "dQw4w9WgXcQ",
    color: "from-orange-700 to-orange-500",
  },
  {
    id: "v4",
    title: "Stakeholder Engagement Forum",
    date: "March 2024",
    duration: "5:21",
    youtubeId: "dQw4w9WgXcQ",
    color: "from-cyan-700 to-cyan-500",
  },
];

export default function PhotosPage({ onNavigate }: PhotosPageProps) {
  const [selectedAlbum, setSelectedAlbum] = useState<string | null>(null);
  const [lightboxPhoto, setLightboxPhoto] = useState<{ albumId: string; photoIndex: number } | null>(null);
  const [playingVideo, setPlayingVideo] = useState<typeof videos[number] | null>(null);

  const activeAlbum = photoAlbums.find((a) => a.id === selectedAlbum);

  const openLightbox = (albumId: string, photoIndex: number) => {
    setLightboxPhoto({ albumId, photoIndex });
    document.body.style.overflow = "hidden";
    document.documentElement.style.overflow = "hidden";
  };

  const closeLightbox = () => {
    setLightboxPhoto(null);
    document.body.style.overflow = "";
    document.documentElement.style.overflow = "";
  };

  const navigateLightbox = (direction: number) => {
    if (!lightboxPhoto) return;
    const album = photoAlbums.find((a) => a.id === lightboxPhoto.albumId);
    if (!album) return;
    const newIndex = (lightboxPhoto.photoIndex + direction + album.photos.length) % album.photos.length;
    setLightboxPhoto({ albumId: lightboxPhoto.albumId, photoIndex: newIndex });
  };

  const currentLightboxAlbum = lightboxPhoto ? photoAlbums.find((a) => a.id === lightboxPhoto.albumId) : null;
  const currentPhoto = currentLightboxAlbum && lightboxPhoto ? currentLightboxAlbum.photos[lightboxPhoto.photoIndex] : null;

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title="Gallery"
        subtitle="Browse photos from ZMC events, workshops, and activities"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Media Centre" }, { label: "Gallery" }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          {!selectedAlbum ? (
            <>
              <div className="text-center mb-10 md:mb-12">
                <Camera className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
                <h2 className="text-xl md:text-2xl mb-3" style={{ color: "var(--primary-dark)" }}>Photo Albums</h2>
                <p className="text-sm md:text-base max-w-[600px] mx-auto" style={{ color: "var(--neutral-600)" }}>
                  Explore our collection of photos from various ZMC events and activities across Zimbabwe.
                </p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6 mb-16">
                {photoAlbums.map((album) => (
                  <button
                    key={album.id}
                    onClick={() => setSelectedAlbum(album.id)}
                    className="text-left rounded-2xl overflow-hidden transition-all hover:-translate-y-1 cursor-pointer border-none p-0 bg-white group"
                    style={{ boxShadow: "var(--shadow-md)", border: "1px solid var(--neutral-100)" }}
                    data-testid={`album-card-${album.id}`}
                  >
                    <div className={`h-48 md:h-56 bg-gradient-to-br ${album.photos[album.cover].color} relative flex items-center justify-center`}>
                      <Camera className="w-12 h-12 md:w-16 md:h-16 text-white/30" />
                      <div className="absolute bottom-3 right-3 bg-black/50 text-white text-xs px-2.5 py-1 rounded-full">
                        {album.photos.length} photos
                      </div>
                      <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                        <ZoomIn className="w-8 h-8 text-white opacity-0 group-hover:opacity-70 transition-opacity" />
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

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                {videos.map((video) => (
                  <button
                    key={video.id}
                    onClick={() => setPlayingVideo(video)}
                    className="text-left rounded-2xl overflow-hidden transition-all hover:-translate-y-1 cursor-pointer border-none p-0 bg-white group"
                    style={{ boxShadow: "var(--shadow-md)", border: "1px solid var(--neutral-100)" }}
                    data-testid={`video-card-${video.id}`}
                  >
                    <div className={`h-48 md:h-56 bg-gradient-to-br ${video.color} relative flex items-center justify-center`}>
                      <div className="w-16 h-16 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/30 group-hover:scale-110 transition-all">
                        <Play className="w-8 h-8 text-white ml-1" fill="white" />
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

              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
                {activeAlbum.photos.map((photo, index) => (
                  <button
                    key={photo.id}
                    onClick={() => openLightbox(activeAlbum.id, index)}
                    className={`aspect-square rounded-xl overflow-hidden cursor-pointer relative group bg-gradient-to-br ${photo.color} border-none p-0`}
                    data-testid={`photo-${photo.id}`}
                  >
                    <div className="absolute inset-0 flex items-center justify-center">
                      <Camera className="w-8 h-8 md:w-10 md:h-10 text-white/20" />
                    </div>
                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center">
                      <ZoomIn className="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                    </div>
                    <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 md:p-3">
                      <p className="text-white text-[10px] md:text-xs leading-tight">{photo.caption}</p>
                    </div>
                  </button>
                ))}
              </div>
            </>
          ) : null}
        </div>
      </div>

      {playingVideo && (
        <div
          className="modal-overlay fixed inset-0 z-[2000] flex items-center justify-center p-4"
          style={{ background: "rgba(0, 0, 0, 0.92)" }}
          onClick={() => setPlayingVideo(null)}
        >
          <div className="modal-content relative max-w-4xl w-full" onClick={(e) => e.stopPropagation()}>
            <button
              onClick={() => setPlayingVideo(null)}
              className="absolute -top-12 right-0 text-white/70 hover:text-white bg-transparent border-none cursor-pointer z-10"
              data-testid="button-close-video"
            >
              <X className="w-8 h-8" />
            </button>
            <div className="aspect-video rounded-xl overflow-hidden bg-black">
              <iframe
                className="w-full h-full"
                src={`https://www.youtube.com/embed/${playingVideo.youtubeId}?autoplay=1`}
                title={playingVideo.title}
                frameBorder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
              />
            </div>
            <div className="mt-4 text-white">
              <h3 className="text-lg md:text-xl font-semibold mb-1">{playingVideo.title}</h3>
              <p className="text-white/60 text-sm">{playingVideo.date}</p>
            </div>
          </div>
        </div>
      )}

      {lightboxPhoto && currentLightboxAlbum && currentPhoto && (
        <div
          className="modal-overlay fixed inset-0 z-[2000] flex items-center justify-center p-4"
          style={{ background: "rgba(0, 0, 0, 0.92)" }}
          onClick={closeLightbox}
        >
          <div className="modal-content relative max-w-4xl w-full" onClick={(e) => e.stopPropagation()}>
            <button
              onClick={closeLightbox}
              className="absolute -top-12 right-0 text-white/70 hover:text-white bg-transparent border-none cursor-pointer z-10"
              data-testid="button-close-lightbox"
            >
              <X className="w-8 h-8" />
            </button>

            <div className={`aspect-[4/3] rounded-xl overflow-hidden bg-gradient-to-br ${currentPhoto.color} flex items-center justify-center relative`}>
              <Camera className="w-16 h-16 md:w-24 md:h-24 text-white/20" />
              <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 md:p-6">
                <p className="text-white text-sm md:text-base">{currentPhoto.caption}</p>
                <p className="text-white/60 text-xs md:text-sm mt-1">
                  {currentLightboxAlbum.title} - Photo {lightboxPhoto.photoIndex + 1} of {currentLightboxAlbum.photos.length}
                </p>
              </div>
            </div>

            <button
              onClick={(e) => { e.stopPropagation(); navigateLightbox(-1); }}
              className="absolute left-2 md:-left-14 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center text-white bg-black/50 hover:bg-black/70 border-none cursor-pointer transition-colors"
              data-testid="button-lightbox-prev"
            >
              <ChevronLeft className="w-6 h-6" />
            </button>
            <button
              onClick={(e) => { e.stopPropagation(); navigateLightbox(1); }}
              className="absolute right-2 md:-right-14 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center text-white bg-black/50 hover:bg-black/70 border-none cursor-pointer transition-colors"
              data-testid="button-lightbox-next"
            >
              <ChevronRight className="w-6 h-6" />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
