export const PORTAL_BASE_URL = "https://portal.zmc.org.zw";
export const PORTAL_LOGIN_URL = `${PORTAL_BASE_URL}/login`;
export const PORTAL_CHOOSE_URL = `${PORTAL_BASE_URL}/choose-portal`;
export const MEDIA_HOUSE_SIGNUP_URL = `${PORTAL_BASE_URL}/media-house-signup`;
export const TRAINING_HUB_URL = "https://traininghub.zmc.org.zw";
export const PRAZ_EGP_URL = "https://egp.praz.org.zw/";
export const CONTACT_EMAIL = "info@zmc.org.zw";

export const SOCIAL_LINKS = {
  facebook: "https://www.facebook.com/zimbabwemediacommission",
  x: "https://x.com/Zim_Media_Com",
  instagram: "https://www.instagram.com/zim_media_com/",
  tiktok: "https://www.tiktok.com/@zim.mediacommission?_r=1&_t=ZS-96xQqrloUvF",
  youtube: "https://www.youtube.com/results?search_query=zimbabwe+media+commission",
  whatsapp: "https://wa.me/263719299150",
} as const;

export function portalAssetUrl(imagePath?: string | null) {
  return imagePath ? `${PORTAL_BASE_URL}/storage/${imagePath}` : null;
}

const BASE_URL = `${PORTAL_BASE_URL}/api/v1`;

async function handleResponse(response: Response) {
  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw {
      status: response.status,
      message: errorData.message || "An unexpected error occurred",
      errors: errorData.errors || null,
    };
  }
  return response.json();
}

async function authenticatedRequest(endpoint: string, options: RequestInit = {}) {
  const token = localStorage.getItem("pr_token");
  const res = await fetch(`${BASE_URL}/pr${endpoint}`, {
    ...options,
    headers: {
      ...options.headers,
      Authorization: `Bearer ${token}`,
    },
  });
  return handleResponse(res);
}

export const portalApi = {
  // Health Check
  getStatus: async () => {
    const res = await fetch(`${BASE_URL}/status`);
    return handleResponse(res);
  },

  // Content Feeds
  getExternalContent: async () => {
    const res = await fetch(`${BASE_URL}/external-content`);
    return handleResponse(res);
  },
  getNews: async (page = 1, perPage = 20) => {
    const res = await fetch(`${BASE_URL}/news?page=${page}&per_page=${perPage}`);
    return handleResponse(res);
  },
  getNotices: async (page = 1, perPage = 20) => {
    const res = await fetch(`${BASE_URL}/notices?page=${page}&per_page=${perPage}`);
    return handleResponse(res);
  },
  getEvents: async (page = 1, perPage = 20) => {
    const res = await fetch(`${BASE_URL}/events?page=${page}&per_page=${perPage}`);
    return handleResponse(res);
  },
  getVacancies: async (page = 1, perPage = 20) => {
    const res = await fetch(`${BASE_URL}/vacancies?page=${page}&per_page=${perPage}`);
    return handleResponse(res);
  },
  getTenders: async (page = 1, perPage = 20) => {
    const res = await fetch(`${BASE_URL}/tenders?page=${page}&per_page=${perPage}`);
    return handleResponse(res);
  },
  getDownloads: async () => {
    const res = await fetch(`${BASE_URL}/downloads`);
    return handleResponse(res);
  },
  getSingleRecord: async (type: "news" | "notices" | "events" | "vacancies" | "tenders", id: string | number) => {
    const res = await fetch(`${BASE_URL}/${type}/${id}`);
    return handleResponse(res);
  },

  // Verification
  verifyAccreditation: async (number: string) => {
    const res = await fetch(`${BASE_URL}/verify/accreditation/${encodeURIComponent(number)}`);
    return handleResponse(res);
  },
  verifyRegistration: async (number: string) => {
    const res = await fetch(`${BASE_URL}/verify/registration/${encodeURIComponent(number)}`);
    return handleResponse(res);
  },

  // Public Directories
  getAccreditedPractitioners: async (params: { page?: number; per_page?: number; search?: string; category?: string; medium?: string; year?: number } = {}) => {
    const query = new URLSearchParams(params as any).toString();
    const res = await fetch(`${BASE_URL}/accredited-practitioners?${query}`);
    return handleResponse(res);
  },
  getRegisteredMediaHouses: async (params: { page?: number; per_page?: number; search?: string; category?: string; province?: string; town?: string } = {}) => {
    const query = new URLSearchParams(params as any).toString();
    const res = await fetch(`${BASE_URL}/registered-mediahouses?${query}`);
    return handleResponse(res);
  },

  // Contact & Submissions
  submitContact: async (data: FormData | object) => {
    const options: RequestInit = {
      method: "POST",
      headers: data instanceof FormData ? {} : { "Content-Type": "application/json" },
      body: data instanceof FormData ? data : JSON.stringify(data),
    };
    const res = await fetch(`${BASE_URL}/contact`, options);
    return handleResponse(res);
  },
  submitComplaint: async (data: FormData | object) => {
    const options: RequestInit = {
      method: "POST",
      headers: data instanceof FormData ? {} : { "Content-Type": "application/json" },
      body: data instanceof FormData ? data : JSON.stringify(data),
    };
    const res = await fetch(`${BASE_URL}/complaints`, options);
    return handleResponse(res);
  },
  submitAppeal: async (data: FormData | object) => {
    const options: RequestInit = {
      method: "POST",
      headers: data instanceof FormData
        ? { Accept: "application/json" }
        : { "Content-Type": "application/json", Accept: "application/json" },
      body: data instanceof FormData ? data : JSON.stringify(data),
    };
    const res = await fetch(`${BASE_URL}/appeals`, options);
    return handleResponse(res);
  },
  initiateAppealPayment: async (data: { appeal_id: number; email: string }) => {
    const res = await fetch(`${BASE_URL}/appeals/payment/initiate`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    return handleResponse(res);
  },
  uploadAppealPaymentProof: async (data: FormData) => {
    const res = await fetch(`${BASE_URL}/appeals/payment/proof`, {
      method: "POST",
      headers: { Accept: "application/json" },
      body: data,
    });
    return handleResponse(res);
  },
  getAppealPaymentStatus: async (data: { appeal_id: number; poll_url: string }) => {
    const res = await fetch(`${BASE_URL}/appeals/payment/status`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    return handleResponse(res);
  },
  subscribeNewsletter: async (data: { full_name: string; email: string }) => {
    const res = await fetch(`${BASE_URL}/public/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    return handleResponse(res);
  },

  // PR Officer Endpoints
  pr: {
    login: async (credentials: { email: string; password: string }) => {
      const res = await fetch(`${BASE_URL}/pr/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(credentials),
      });
      const data = await handleResponse(res);
      if (data.token) localStorage.setItem("pr_token", data.token);
      return data;
    },
    logout: () => {
      localStorage.removeItem("pr_token");
    },
    
    // Generic resource handler for News, Notices, Events, Vacancies, Tenders, Downloads
    list: async (resource: "news" | "notices" | "events" | "vacancies" | "tenders" | "downloads", params: { search?: string; is_published?: boolean; per_page?: number } = {}) => {
      const query = new URLSearchParams(params as any).toString();
      return authenticatedRequest(`/${resource}${query ? `?${query}` : ""}`);
    },
    create: async (resource: "news" | "notices" | "events" | "vacancies" | "tenders" | "downloads", data: FormData | object) => {
      return authenticatedRequest(`/${resource}`, {
        method: "POST",
        headers: data instanceof FormData ? {} : { "Content-Type": "application/json" },
        body: data instanceof FormData ? data : JSON.stringify(data),
      });
    },
    getOne: async (resource: "news" | "notices" | "events" | "vacancies" | "tenders" | "downloads", id: string | number) => {
      return authenticatedRequest(`/${resource}/${id}`);
    },
    update: async (resource: "news" | "notices" | "events" | "vacancies" | "tenders" | "downloads", id: string | number, data: FormData | object) => {
      return authenticatedRequest(`/${resource}/${id}`, {
        method: "PUT",
        headers: data instanceof FormData ? {} : { "Content-Type": "application/json" },
        body: data instanceof FormData ? data : JSON.stringify(data),
      });
    },
    delete: async (resource: "news" | "notices" | "events" | "vacancies" | "tenders" | "downloads", id: string | number) => {
      return authenticatedRequest(`/${resource}/${id}`, { method: "DELETE" });
    },
    togglePublish: async (resource: "news" | "notices" | "events" | "vacancies" | "tenders", id: string | number) => {
      return authenticatedRequest(`/${resource}/${id}/publish`, { method: "PATCH" });
    },
    getStats: async () => {
      return authenticatedRequest("/stats");
    },
    getSubscribers: async () => {
      return authenticatedRequest("/subscribers");
    },
  },
};
