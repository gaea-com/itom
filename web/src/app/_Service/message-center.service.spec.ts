import { TestBed } from '@angular/core/testing';

import { MessageCenterService } from './message-center.service';

describe('MessageCenterService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: MessageCenterService = TestBed.get(MessageCenterService);
    expect(service).toBeTruthy();
  });
});
