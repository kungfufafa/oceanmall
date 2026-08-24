import { Platform } from 'react-native';

function defaultHost(): string {
  if (process.env.EXPO_PUBLIC_API_URL) {
    return process.env.EXPO_PUBLIC_API_URL;
  }

  const host = Platform.OS === 'android' ? '10.0.2.2' : '127.0.0.1';

  return `http://${host}:8000/api/v1`;
}

export const API_URL = defaultHost().replace(/\/$/, '');
