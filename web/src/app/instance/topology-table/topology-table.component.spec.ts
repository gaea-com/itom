import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TopologyTableComponent } from './topology-table.component';

describe('TopologyTableComponent', () => {
  let component: TopologyTableComponent;
  let fixture: ComponentFixture<TopologyTableComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TopologyTableComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TopologyTableComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
