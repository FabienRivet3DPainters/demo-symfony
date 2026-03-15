import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 10 },
    { duration: '1m',  target: 50 },
    { duration: '30s', target: 100 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<200'],
    http_req_failed:   ['rate<0.01'],
  },
  cloud: {
    distribution: {
      'amazon:fr:paris': { loadZone: 'amazon:fr:paris', percent: 100 },
    },
  },
};

const BASE_URL = __ENV.BASE_URL;

export function setup() {
  const res = http.post(`${BASE_URL}/api/login_check`, JSON.stringify({
    username: __ENV.K6_USERNAME,
    password: __ENV.K6_PASSWORD,
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  const token = res.json('token');
  console.log(`Token JWT récupéré: ${token ? 'OK' : 'ECHEC'}`);
  return { token };
}

export default function (data) {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${data.token}`,
  };

  let res = http.get(`${BASE_URL}/`);
  check(res, {
    'homepage status 200': (r) => r.status === 200,
    'homepage p95 < 200ms': (r) => r.timings.duration < 200,
  });

  sleep(1);

  res = http.get(`${BASE_URL}/api/products`, { headers });
  check(res, {
    'api products status 200': (r) => r.status === 200,
    'api products p95 < 200ms': (r) => r.timings.duration < 200,
  });

  sleep(1);
}