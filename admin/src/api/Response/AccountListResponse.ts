/* eslint-disable max-len */

import { UserResource } from '../Resource/UserResource';

export interface AccountListResponse {
  data: [
    UserResource
  ],
  pager: {
    max: number,
    current: number,
    prev?: number,
    next?: number,
    total?: number
  }
}
