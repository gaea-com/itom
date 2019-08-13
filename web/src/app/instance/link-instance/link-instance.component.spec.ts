import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LinkInstanceComponent } from './link-instance.component';

describe('LinkInstanceComponent', () => {
  let component: LinkInstanceComponent;
  let fixture: ComponentFixture<LinkInstanceComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ LinkInstanceComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LinkInstanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
